<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'valor',
        'frequencia_semanal',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'frequencia_semanal' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    public function alunos(): HasMany
    {
        return $this->hasMany(Aluno::class);
    }
}