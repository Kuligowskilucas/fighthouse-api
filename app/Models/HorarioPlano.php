<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioPlano extends Model
{
    protected $table = 'horarios_plano';

    protected $fillable = ['plano_id', 'horario'];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }
}