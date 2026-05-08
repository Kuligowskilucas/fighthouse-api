<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensalidade extends Model
{
    use HasFactory;

    protected $fillable = [
        'aluno_id',
        'mes_referencia',
        'valor',
        'data_vencimento',
        'data_pagamento',
        'forma_pagamento',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'mes_referencia' => 'date',
            'valor' => 'decimal:2',
            'data_vencimento' => 'date',
            'data_pagamento' => 'date',
        ];
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    /**
     * Indica se a mensalidade está paga.
     */
    public function estaPaga(): bool
    {
        return $this->data_pagamento !== null;
    }

    /**
     * Indica se está atrasada (venceu e não foi paga).
     * Tolerância zero conforme DECISOES.md.
     */
    public function estaAtrasada(): bool
    {
       return !$this->estaPaga() && $this->data_vencimento->lt(\Carbon\Carbon::today());
    }
}