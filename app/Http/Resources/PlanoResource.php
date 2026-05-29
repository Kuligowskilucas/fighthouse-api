<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'nome'         => $this->nome,
            'valor' => (float) $this->valor,
            'ativo'        => $this->ativo,
            'dias_semana'  => $this->dias_semana,
            'horarios'     => $this->whenLoaded('horarios', fn() =>
                $this->horarios->pluck('horario')->values()
            ),
            'alunos_count' => $this->whenCounted('alunos'),
        ];
    }
}