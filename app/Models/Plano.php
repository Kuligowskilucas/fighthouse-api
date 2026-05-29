<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\HorarioPlano;

class Plano extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'valor',
        'frequencia_semanal',
        'ativo',
        'dias_semana',
    ];
    protected $attributes = [
        'ativo' => true,
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

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioPlano::class)->orderBy('horario');
    }
}