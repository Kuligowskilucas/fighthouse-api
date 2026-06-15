<?php

namespace Tests\Unit;

use App\Services\MercadoPagoSignatureValidator;
use Illuminate\Http\Request;
use Tests\TestCase;

class MercadoPagoSignatureValidatorTest extends TestCase
{
    private function montarRequest(string $secretParaAssinar, string $dataId, string $requestId, string $ts): Request
    {
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $v1 = hash_hmac('sha256', $manifest, $secretParaAssinar);

        $request = Request::create(
            '/api/webhooks/mercadopago',
            'POST',
            [], [], [], [],
            json_encode(['type' => 'payment', 'action' => 'payment.updated', 'data' => ['id' => $dataId]])
        );
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('x-signature', "ts={$ts},v1={$v1}");
        $request->headers->set('x-request-id', $requestId);

        return $request;
    }

    public function test_aceita_assinatura_valida(): void
    {
        config(['services.mercadopago.webhook_secret' => 'segredo_de_teste']);

        $request = $this->montarRequest('segredo_de_teste', '12345', 'req-abc', '1700000000000');

        $this->assertTrue((new MercadoPagoSignatureValidator())->valido($request));
    }

    public function test_rejeita_assinatura_adulterada(): void
    {
        config(['services.mercadopago.webhook_secret' => 'segredo_de_teste']);

        // Assinado com OUTRO secret → v1 não bate.
        $request = $this->montarRequest('secret_errado', '12345', 'req-abc', '1700000000000');

        $this->assertFalse((new MercadoPagoSignatureValidator())->valido($request));
    }

    public function test_rejeita_quando_secret_nao_configurado(): void
    {
        config(['services.mercadopago.webhook_secret' => '']);

        $request = $this->montarRequest('qualquer', '12345', 'req-abc', '1700000000000');

        $this->assertFalse((new MercadoPagoSignatureValidator())->valido($request));
    }
}