<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MensalidadeResource;
use App\Models\Aluno;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->get();

        $valorAtrasado = (float) $atrasadasGeral->sum('valor');

        return response()->json([
            'mes_referencia' => $mesReferencia->format('Y-m-d'),
            'alunos_ativos' => $alunosAtivos,
            'mensalidades_do_mes' => [
                'total' => $mensalidadesDoMes->count(),
                'pagas' => $mensalidadesDoMes->whereNotNull('data_pagamento')->count(),
                'em_aberto' => $mensalidadesDoMes->whereNull('data_pagamento')->count(),
            ],
            'financeiro' => [
                'recebido_no_mes' => $recebido,
                'a_receber_no_mes' => $aReceber,
                'total_atrasado_geral' => $valorAtrasado,
            ],
            'inadimplencia' => [
                'quantidade_atrasadas' => $atrasadasGeral->count(),
                'alunos_inadimplentes' => $atrasadasGeral->pluck('aluno_id')->unique()->count(),
            ],
        ]);
    }

    /**
     * Lista de alunos inadimplentes (mensalidades atrasadas).
     * Útil pra tela de "alunos pra cobrar".
     */
    public function inadimplentes(Request $request): JsonResponse
    {
        $mensalidades = Mensalidade::query()
            ->with('aluno.plano')
            ->whereNull('data_pagamento')
            ->where('data_vencimento', '<', Carbon::today())
            ->orderBy('data_vencimento', 'asc')
            ->get();

        $porAluno = $mensalidades
            ->groupBy('aluno_id')
            ->map(function ($mensalidadesDoAluno) {
                $aluno = $mensalidadesDoAluno->first()->aluno;

                return [
                    'aluno' => [
                        'id' => $aluno->id,
                        'nome' => $aluno->nome,
                        'telefone' => $aluno->telefone,
                        'email' => $aluno->email,
                        'plano' => $aluno->plano->nome,
                    ],
                    'quantidade_atrasadas' => $mensalidadesDoAluno->count(),
                    'valor_total_devido' => (float) $mensalidadesDoAluno->sum('valor'),
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
            'data' => $porAluno,
            'total_alunos_inadimplentes' => $porAluno->count(),
            'valor_total_devido' => (float) $mensalidades->sum('valor'),
        ]);
    }
}