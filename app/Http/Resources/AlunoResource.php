<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlunoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'plano' => new PlanoResource($this->whenLoaded('plano')),
            'plano_id' => $this->plano_id,
            'valor_personalizado' => $this->valor_personalizado !== null
                ? (float) $this->valor_personalizado
                : null,
            'valor_efetivo' => (float) $this->valorMensalidade(),
            'dia_vencimento' => $this->dia_vencimento,
            'data_matricula' => $this->data_matricula?->format('Y-m-d'),
            'ativo' => $this->ativo,
            'observacoes' => $this->observacoes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}