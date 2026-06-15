<?php

namespace Tests\Feature\Pagamento;

use App\Models\Aluno;
use App\Models\Mensalidade;
use App\Models\Transacao;
use App\Services\MercadoPagoService;
use App\Services\MercadoPagoSignatureValidator;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MercadoPago\Resources\Payment;
use Tests\TestCase;

class WebhookMercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanoSeeder::class);
    }

    /** Monta um Payment real do SDK (satisfaz o return type do mock). */
    private function fakePayment(array $attrs): Payment
    {
        $payment = new Payment();
        foreach ($attrs as $chave => $valor) {
            $payment->{$chave} = $valor;
        }
        return $payment;
    }

    private function criarCenario(float $valor): array
    {
        $aluno = Aluno::factory()->create();
        $mensalidade = Mensalidade::factory()->create([
            'aluno_id'       => $aluno->id,
            'data_pagamento' => null,
            'valor'          => $valor,
        ]);
        $transacao = Transacao::create([
            'mensalidade_id' => $mensalidade->id,
            'status'         => Transacao::STATUS_PENDING,
            'valor'          => $valor,
        ]);

        $this->mock(MercadoPagoSignatureValidator::class, fn ($m) =>
            $m->shouldReceive('valido')->andReturnTrue()
        );

        return [$mensalidade, $transacao];
    }

    public function test_pagamento_aprovado_marca_mensalidade_paga(): void
    {
        [$mensalidade, $transacao] = $this->criarCenario(169.90);

        $pagamento = $this->fakePayment([
            'id'                 => '123',
            'status'             => 'approved',
            'status_detail'      => 'accredited',
            'transaction_amount' => 169.90,
            'external_reference' => (string) $transacao->id,
            'payment_method_id'  => 'visa',
            'payment_type_id'    => 'credit_card',
            'date_approved'      => '2026-06-15T10:00:00.000-03:00',
        ]);

        $this->mock(MercadoPagoService::class, fn ($m) =>
            $m->shouldReceive('buscarPagamento')->with('123')->andReturn($pagamento)
        );

        $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '123'],
        ])->assertNoContent();

        $mensalidade->refresh();
        $this->assertNotNull($mensalidade->data_pagamento);
        $this->assertSame('mercado_pago', $mensalidade->forma_pagamento);
        $this->assertSame('approved', $transacao->fresh()->status);
        $this->assertSame('123', $transacao->fresh()->payment_id);
    }

    public function test_valor_divergente_nao_marca_paga(): void
    {
        [$mensalidade, $transacao] = $this->criarCenario(169.90);

        $pagamento = $this->fakePayment([
            'id'                 => '999',
            'status'             => 'approved',
            'transaction_amount' => 1.00,
            'external_reference' => (string) $transacao->id,
        ]);

        $this->mock(MercadoPagoService::class, fn ($m) =>
            $m->shouldReceive('buscarPagamento')->andReturn($pagamento)
        );

        $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '999'],
        ])->assertNoContent();

        $this->assertNull($mensalidade->refresh()->data_pagamento);
    }

    public function test_status_pendente_nao_marca_paga(): void
    {
        [$mensalidade, $transacao] = $this->criarCenario(169.90);

        $pagamento = $this->fakePayment([
            'id'                 => '555',
            'status'             => 'pending',
            'transaction_amount' => 169.90,
            'external_reference' => (string) $transacao->id,
        ]);

        $this->mock(MercadoPagoService::class, fn ($m) =>
            $m->shouldReceive('buscarPagamento')->andReturn($pagamento)
        );

        $this->postJson('/api/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '555'],
        ])->assertNoContent();

        $this->assertNull($mensalidade->refresh()->data_pagamento);
        $this->assertSame('pending', $transacao->fresh()->status);
    }
}