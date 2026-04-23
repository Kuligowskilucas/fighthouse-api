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
            'valor_efetivo' => $this->relationLoaded('plano')
                ? (float) $this->valorMensalidade()
                : null,
            'dia_vencimento' => $this->dia_vencimento,
            'data_matricula' => $this->data_matricula?->format('Y-m-d'),
            'dias_matriculado' => $this->diasMatriculado(),
            'ativo' => $this->ativo,
            'observacoes' => $this->observacoes,

            // Campos pesados: só aparecem se a relação foi carregada
            'mensalidades' => MensalidadeResource::collection($this->whenLoaded('mensalidades')),
            'resumo_financeiro' => $this->when(
                $this->relationLoaded('mensalidades'),
                fn () => $this->resumoFinanceiro()
            ),
            'mensalidade_atual' => $this->when(
                $this->relationLoaded('mensalidades'),
                fn () => $this->mensalidadeAtual() 
                    ? new MensalidadeResource($this->mensalidadeAtual()) 
                    : null
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}