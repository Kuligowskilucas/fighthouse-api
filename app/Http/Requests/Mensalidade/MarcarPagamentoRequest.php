<?php

namespace App\Http\Requests\Mensalidade;

use Illuminate\Foundation\Http\FormRequest;

class MarcarPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_pagamento' => ['nullable', 'date', 'before_or_equal:today'],
            'forma_pagamento' => ['required', 'string', 'in:pix,dinheiro,cartao,transferencia'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'data_pagamento.before_or_equal' => 'A data de pagamento não pode ser no futuro.',
            'forma_pagamento.required' => 'Informe a forma de pagamento.',
        ];
    }
}