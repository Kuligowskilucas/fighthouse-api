<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MensalidadeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aluno_id' => $this->aluno_id,
            'aluno' => new AlunoResource($this->whenLoaded('aluno')),
            'mes_referencia' => $this->mes_referencia?->format('Y-m-d'),
            'valor' => (float) $this->valor,
            'data_vencimento' => $this->data_vencimento?->format('Y-m-d'),
            'data_pagamento' => $this->data_pagamento?->format('Y-m-d'),
            'forma_pagamento' => $this->forma_pagamento,
            'status' => $this->calcularStatus(),
            'observacoes' => $this->observacoes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function calcularStatus(): string
    {
        if ($this->estaPaga()) {
            return 'paga';
        }

        if ($this->estaAtrasada()) {
            return 'atrasada';
        }

        return 'aberta';
    }
}