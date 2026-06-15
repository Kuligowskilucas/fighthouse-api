<?php

namespace App\Actions;

use App\Models\Mensalidade;
use Carbon\Carbon;

class MarcarMensalidadePaga
{
    /**
     * Marca a mensalidade como paga de forma idempotente.
     *
     * Retorna true se marcou agora, false se JÁ estava paga.
     * O guard estaPaga() é a garantia de idempotência: o MP dispara o
     * webhook várias vezes pro mesmo pagamento, e a 2ª, 3ª vez caem aqui
     * e não reprocessam nada.
     */
    public function executar(Mensalidade $mensalidade, string $formaPagamento, ?string $dataPagamento = null, ?string $observacoes = null): bool 
    {
        if ($mensalidade->estaPaga()) {
            return false;
        }

        $mensalidade->update([
            'data_pagamento'  => $dataPagamento ?? Carbon::today(),
            'forma_pagamento' => $formaPagamento,
            'observacoes'     => $observacoes ?? $mensalidade->observacoes,
        ]);

        return true;
    }
}