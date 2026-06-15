<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transacao extends Model
{
    use HasFactory;

    protected $table = 'transacoes';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'mensalidade_id',
        'status',
        'preference_id',
        'payment_id',
        'valor',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'valor'   => 'decimal:2',
            'payload' => 'array',
        ];
    }

    public function mensalidade(): BelongsTo
    {
        return $this->belongsTo(Mensalidade::class);
    }

    public function estaAprovada(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}