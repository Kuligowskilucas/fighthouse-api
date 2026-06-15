<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoSignatureValidator
{
    public function valido(Request $request): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (empty($secret)) {
            return false;
        }

        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');

        $dataId = $request->input('data.id') ?? $request->query('data_id');

        if (empty($xSignature) || empty($xRequestId) || empty($dataId)) {
            return false;
        }

        $ts = null;
        $v1 = null;
        foreach (explode(',', $xSignature) as $parte) {
            [$chave, $valor] = array_pad(explode('=', $parte, 2), 2, null);
            $chave = trim((string) $chave);
            $valor = trim((string) $valor);
            if ($chave === 'ts') {
                $ts = $valor;
            } elseif ($chave === 'v1') {
                $v1 = $valor;
            }
        }

        if (empty($ts) || empty($v1)) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        $hashCalculado = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($hashCalculado, $v1);
    }
}