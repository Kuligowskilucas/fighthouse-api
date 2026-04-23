<?php

namespace App\Http\Requests\Mensalidade;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMensalidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999.99'],
            'data_vencimento' => ['sometimes', 'required', 'date'],
            'data_pagamento' => ['sometimes', 'nullable', 'date'],
            'forma_pagamento' => ['sometimes', 'nullable', 'string', 'in:pix,dinheiro,cartao,transferencia'],
            'observacoes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}