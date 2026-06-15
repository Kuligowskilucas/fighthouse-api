<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoSignatureValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Actions\MarcarMensalidadePaga;
use App\Models\Transacao;
use App\Services\MercadoPagoService;

class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request, MercadoPagoSignatureValidator $validator, MercadoPagoService $mp, MarcarMensalidadePaga $marcarPaga): Response|JsonResponse {
        if (! $validator->valido($request)) {
            Log::warning('Webhook MP rejeitado: assinatura inválida', [
                'x_request_id' => $request->header('x-request-id'),
                'ip'           => $request->ip(),
            ]);
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        if ($request->input('type') !== 'payment') {
            return response()->noContent();
        }

        $paymentId = $request->input('data.id');
        if (empty($paymentId)) {
            return response()->noContent();
        }

        try {
            $payment = $mp->buscarPagamento((string) $paymentId);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao consultar pagamento.'], 502);
        }

        $transacao = $payment->external_reference
            ? Transacao::find($payment->external_reference)
            : null;

        if (! $transacao) {
            Log::warning('Webhook MP: transação não encontrada', [
                'payment_id'         => $payment->id,
                'external_reference' => $payment->external_reference,
            ]);
            return response()->noContent(); 
        }

        $transacao->update([
            'payment_id' => (string) $payment->id,
            'status'     => $payment->status,
            'payload'    => [
                'status'             => $payment->status,
                'status_detail'      => $payment->status_detail ?? null,
                'transaction_amount' => $payment->transaction_amount,
                'payment_method_id'  => $payment->payment_method_id ?? null,
                'payment_type_id'    => $payment->payment_type_id ?? null,
                'date_approved'      => $payment->date_approved ?? null,
            ],
        ]);

        if ($payment->status === 'approved') {
            $esperado = (int) round(((float) $transacao->valor) * 100);
            $pago     = (int) round(((float) $payment->transaction_amount) * 100);

            if ($pago !== $esperado) {
                Log::critical('Webhook MP: valor divergente — NÃO marcado como pago', [
                    'transacao_id' => $transacao->id,
                    'esperado'     => $transacao->valor,
                    'pago'         => $payment->transaction_amount,
                    'payment_id'   => $payment->id,
                ]);
                return response()->noContent(); 
            }
    
            $marcarPaga->executar(
                $transacao->mensalidade,
                'mercado_pago',
                null,
                'Pago via Mercado Pago (payment ' . $payment->id . ')',
            );
        }

        return response()->noContent();
    }
}