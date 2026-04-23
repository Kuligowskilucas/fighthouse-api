<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aluno extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'telefone',
        'email',
        'plano_id',
        'valor_personalizado',
        'dia_vencimento',
        'data_matricula',
        'ativo',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'valor_personalizado' => 'decimal:2',
            'dia_vencimento' => 'integer',
            'data_matricula' => 'date',
            'ativo' => 'boolean',
        ];
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function mensalidades(): HasMany
    {
        return $this->hasMany(Mensalidade::class);
    }

    /**
     * Retorna o valor efetivo da mensalidade:
     * valor_personalizado se existir, senão o valor do plano.
     */
    public function valorMensalidade(): string
    {
        return $this->valor_personalizado ?? $this->plano->valor;
    }
}