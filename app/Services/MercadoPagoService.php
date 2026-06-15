<?php

namespace App\Services;

use App\Models\Transacao;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Preference;

class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
    }

    public function criarPreferenceParaTransacao(Transacao $transacao): Preference
    {
        $transacao->loadMissing('mensalidade.aluno');

        $mensalidade = $transacao->mensalidade;
        $aluno       = $mensalidade->aluno;
        $frontend    = rtrim(config('services.mercadopago.frontend_url'), '/');

        // E-mail é opcional no teu sistema — só manda se existir.
        $payer = ['name' => $aluno->nome];
        if (! empty($aluno->email)) {
            $payer['email'] = $aluno->email;
        }

        $client = new PreferenceClient();

        return $client->create([
            'items' => [[
                'id'          => (string) $mensalidade->id,
                'title'       => 'Mensalidade Fight House Club',
                'description' => 'Referência ' . $mensalidade->mes_referencia->format('m/Y'),
                'quantity'    => 1,
                'unit_price'  => (float) $transacao->valor,
                'currency_id' => 'BRL',
            ]],
            'payer'              => $payer,
            'external_reference' => (string) $transacao->id,
            'notification_url'   => config('services.mercadopago.webhook_url'),
            'back_urls' => [
                'success' => $frontend . '/pagamento/sucesso',
                'pending' => $frontend . '/pagamento/pendente',
                'failure' => $frontend . '/pagamento/falha',
            ],
            'auto_return' => 'approved',
        ]);
    }
}