<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mensalidade;
use App\Models\Transacao;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagamentoController extends Controller
{
    public function iniciar(Request $request, Mensalidade $mensalidade, MercadoPagoService $mp): JsonResponse
    {
        $user = $request->user();
        
        abort_unless(
            $user->aluno_id && $mensalidade->aluno_id === $user->aluno_id,
            403,
            'Esta mensalidade não pertence a você.'
        );

        if ($mensalidade->estaPaga()) {
            return response()->json([
                'message' => 'Esta mensalidade já está paga.',
            ], 409);
        }

        $transacao = Transacao::create([
            'mensalidade_id' => $mensalidade->id,
            'status'         => Transacao::STATUS_PENDING,
            'valor'          => $mensalidade->valor,
        ]);

        try {
            $preference = $mp->criarPreferenceParaTransacao($transacao);
            $transacao->update(['preference_id' => $preference->id]);
        } catch (\Throwable $e) {
            $transacao->delete();
            report($e);

            return response()->json([
                'message' => 'Não foi possível iniciar o pagamento. Tente novamente em instantes.',
            ], 502);
        }

        return response()->json([
            'transacao_id' => $transacao->id,
            'init_point'   => $preference->init_point,
        ]);
    }
}