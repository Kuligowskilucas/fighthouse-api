<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MensalidadeResource;
use App\Models\Aluno;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\CicloCobranca;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    /**
     * Retorna o resumo do mês de referência (padrão: mês atual).
     */
    public function resumoMensal(Request $request): JsonResponse
    {
        $mesReferencia = $request->filled('mes')
            ? Carbon::parse($request->string('mes'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        $mensalidadesDoMes = Mensalidade::where('mes_referencia', $mesReferencia)->get();

        $alunosAtivos = Aluno::where('ativo', true)->count();

        $recebido = (float) $mensalidadesDoMes
            ->whereNotNull('data_pagamento')
            ->sum('valor');

        $aReceber = (float) $mensalidadesDoMes
            ->whereNull('data_pagamento')
            ->sum('valor');

        $atrasadasGeral = Mensalidade::query()
            ->whereNull('data_pagamento')
            ->where('data_vencimento', '<', Carbon::today())
            ->whereHas('aluno', fn ($q) => $q->where('ativo', true))
            ->get();

        $valorAtrasado = (float) $atrasadasGeral->sum('valor');

        return response()->json([
            'mes_referencia'    => $mesReferencia->format('Y-m-d'),
            'alunos_ativos'     => $alunosAtivos,
            'mensalidades_do_mes' => [
                'total'    => $mensalidadesDoMes->count(),
                'pagas'    => $mensalidadesDoMes->whereNotNull('data_pagamento')->count(),
                'em_aberto' => $mensalidadesDoMes->whereNull('data_pagamento')->count(),
            ],
            'financeiro' => [
                'recebido_no_mes'      => $recebido,
                'a_receber_no_mes'     => $aReceber,
                'total_atrasado_geral' => $valorAtrasado,
            ],
            'inadimplencia' => [
                'quantidade_atrasadas'  => $atrasadasGeral->count(),
                'alunos_inadimplentes'  => $atrasadasGeral->pluck('aluno_id')->unique()->count(),
            ],
        ]);
    }

    /**
     * Lista de alunos inadimplentes (mensalidades atrasadas).
     */
    public function inadimplentes(Request $request): JsonResponse
    {
        $mensalidades = Mensalidade::query()
            ->with('aluno.plano')
            ->whereNull('data_pagamento')
            ->where('data_vencimento', '<', Carbon::today())
            ->whereHas('aluno', fn ($q) => $q->where('ativo', true))
            ->orderBy('data_vencimento', 'asc')
            ->get();

        $porAluno = $mensalidades
            ->groupBy('aluno_id')
            ->map(function ($mensalidadesDoAluno) {
                $aluno = $mensalidadesDoAluno->first()->aluno;

                return [
                    'aluno' => [
                        'id'       => $aluno->id,
                        'nome'     => $aluno->nome,
                        'telefone' => $aluno->telefone,
                        'email'    => $aluno->email,
                        'plano'    => $aluno->plano->nome,
                    ],
                    'quantidade_atrasadas'  => $mensalidadesDoAluno->count(),
                    'valor_total_devido'    => (float) $mensalidadesDoAluno->sum('valor'),
                    'mensalidade_mais_antiga' => MensalidadeResource::make(
                        $mensalidadesDoAluno->sortBy('data_vencimento')->first()
                    ),
                    'dias_atraso' => (int) Carbon::parse(
                        $mensalidadesDoAluno->sortBy('data_vencimento')->first()->data_vencimento
                    )->diffInDays(Carbon::today()),
                ];
            })
            ->values();

        return response()->json([
            'data'                     => $porAluno,
            'total_alunos_inadimplentes' => $porAluno->count(),
            'valor_total_devido'       => (float) $mensalidades->sum('valor'),
        ]);
    }

    /**
     * Pagamentos recebidos no dia atual.
     * Filtra mensalidades onde data_pagamento = hoje, ordenadas por updated_at desc
     * (os mais recentes primeiro, útil pra conferir o que acabou de entrar).
     */
    public function recebidosHoje(): JsonResponse
    {
        $mensalidades = Mensalidade::query()
            ->with('aluno')
            ->whereDate('data_pagamento', Carbon::today())
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'data'           => MensalidadeResource::collection($mensalidades),
            'total_recebido' => (float) $mensalidades->sum('valor'),
            'quantidade'     => $mensalidades->count(),
        ]);
    }

    /**
     * Resumo do dashboard por ciclo de cobrança (fechamento dia 5).
     * ?periodo=YYYY-MM define o ciclo; padrão = ciclo aberto atual.
     * "Pagante de junho" = quem pagou dentro da janela [05/06, 05/07),
     * mesmo que a mensalidade seja de outro mês (vai em mes_referencia).
     */
    public function resumoCiclo(Request $request): JsonResponse
    {
        $ciclo = $request->filled('periodo')
            ? CicloCobranca::deReferencia($request->string('periodo'))
            : CicloCobranca::atual();

        $pagas = Mensalidade::query()
            ->with('aluno')
            ->whereNotNull('data_pagamento')
            ->where('data_pagamento', '>=', $ciclo->inicio->toDateString())
            ->where('data_pagamento', '<', $ciclo->fim->toDateString())
            ->orderBy('data_pagamento', 'desc')
            ->get();

        $atual = CicloCobranca::atual();
        $primeiroPagamento = Mensalidade::whereNotNull('data_pagamento')->min('data_pagamento');

        $temAnterior = $primeiroPagamento !== null
            && CicloCobranca::queContem(CarbonImmutable::parse($primeiroPagamento))
                ->inicio->lt($ciclo->inicio);

        return response()->json([
            'periodo' => [
                'referencia'   => $ciclo->referencia(),
                'inicio'       => $ciclo->inicio->toDateString(),
                'fim'          => $ciclo->fim->toDateString(),
                'tem_anterior' => $temAnterior,
                'tem_proximo'  => $ciclo->inicio->lt($atual->inicio),
            ],
            'total_recebido' => (float) $pagas->sum('valor'),
            'quantidade'     => $pagas->count(),
            'pagantes'       => MensalidadeResource::collection($pagas),
        ]);
    }
}