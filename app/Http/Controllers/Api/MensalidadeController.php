<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mensalidade\MarcarPagamentoRequest;
use App\Http\Requests\Mensalidade\StoreMensalidadeRequest;
use App\Http\Requests\Mensalidade\UpdateMensalidadeRequest;
use App\Http\Resources\MensalidadeResource;
use App\Models\Mensalidade;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Mensalidade\GerarMensalidadesRequest;
use App\Services\GeradorDeMensalidades;

class MensalidadeController extends Controller
{
    public function index(Request $request)
    {
        $mensalidades = Mensalidade::query()
            ->with('aluno.plano')
            ->when(
                $request->filled('aluno_id'),
                fn ($q) => $q->where('aluno_id', $request->integer('aluno_id'))
            )
            ->when(
                $request->filled('mes_referencia'),
                fn ($q) => $q->where('mes_referencia', $request->date('mes_referencia')->startOfMonth())
            )
            ->when(
                $request->filled('status'),
                function ($q) use ($request) {
                    $status = $request->string('status')->toString();
                    $hoje = Carbon::today();

                    match ($status) {
                        'paga' => $q->whereNotNull('data_pagamento'),
                        'atrasada' => $q->whereNull('data_pagamento')
                            ->where('data_vencimento', '<', $hoje),
                        'aberta' => $q->whereNull('data_pagamento')
                            ->where('data_vencimento', '>=', $hoje),
                        default => null,
                    };
                }
            )
            ->orderBy('data_vencimento', 'desc')
            ->paginate($request->integer('per_page', 20));

        return MensalidadeResource::collection($mensalidades);
    }

    public function store(StoreMensalidadeRequest $request): MensalidadeResource
    {
        $mensalidade = Mensalidade::create($request->validated());
        $mensalidade->load('aluno.plano');

        return new MensalidadeResource($mensalidade);
    }

    public function show(Mensalidade $mensalidade): MensalidadeResource
    {
        $mensalidade->load('aluno.plano');

        return new MensalidadeResource($mensalidade);
    }

    public function update(UpdateMensalidadeRequest $request, Mensalidade $mensalidade): MensalidadeResource
    {
        $mensalidade->update($request->validated());
        $mensalidade->load('aluno.plano');

        return new MensalidadeResource($mensalidade);
    }

    public function destroy(Mensalidade $mensalidade): Response
    {
        $mensalidade->delete();

        return response()->noContent();
    }

    /**
     * Marca a mensalidade como paga.
     * Endpoint específico porque é a ação mais comum do sistema.
     */
    public function marcarPagamento(
        MarcarPagamentoRequest $request,
        Mensalidade $mensalidade
    ): MensalidadeResource|JsonResponse {
        if ($mensalidade->estaPaga()) {
            return response()->json([
                'message' => 'Essa mensalidade já foi paga em ' 
                    . $mensalidade->data_pagamento->format('d/m/Y') . '.',
            ], 409);
        }

        $mensalidade->update([
            'data_pagamento' => $request->input('data_pagamento', Carbon::today()),
            'forma_pagamento' => $request->forma_pagamento,
            'observacoes' => $request->observacoes ?? $mensalidade->observacoes,
        ]);

        $mensalidade->load('aluno.plano');

        return new MensalidadeResource($mensalidade);
    }

    /**
     * Desfaz o pagamento (útil caso marcado por engano).
     */
    public function desfazerPagamento(Mensalidade $mensalidade): MensalidadeResource|JsonResponse
    {
        if (! $mensalidade->estaPaga()) {
            return response()->json([
                'message' => 'Essa mensalidade não está marcada como paga.',
            ], 409);
        }

        $mensalidade->update([
            'data_pagamento' => null,
            'forma_pagamento' => null,
        ]);

        $mensalidade->load('aluno.plano');

        return new MensalidadeResource($mensalidade);
    }

    public function gerar(GerarMensalidadesRequest $request, GeradorDeMensalidades $gerador): JsonResponse 
    {
        $mesReferencia = $request->filled('mes_referencia')
            ? Carbon::createFromFormat('Y-m', $request->input('mes_referencia'))
            : null;

        $resultado = $gerador->gerar($mesReferencia);

        return response()->json($resultado);
    }
}