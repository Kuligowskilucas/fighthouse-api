<?php

namespace App\Http\Requests\Mensalidade;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreMensalidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normaliza mes_referencia pra sempre ser dia 1
        if ($this->filled('mes_referencia')) {
            $this->merge([
                'mes_referencia' => Carbon::parse($this->mes_referencia)
                    ->startOfMonth()
                    ->format('Y-m-d'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'aluno_id' => ['required', 'integer', 'exists:alunos,id'],
            'mes_referencia' => [
                'required',
                'date',
                'unique:mensalidades,mes_referencia,NULL,id,aluno_id,' . $this->input('aluno_id'),
            ],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'data_vencimento' => ['required', 'date'],
            'data_pagamento' => ['nullable', 'date'],
            'forma_pagamento' => ['nullable', 'string', 'in:pix,dinheiro,cartao,transferencia'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'mes_referencia.unique' => 'Já existe mensalidade registrada pra esse aluno nesse mês.',
            'forma_pagamento.in' => 'Forma de pagamento inválida.',
        ];
    }
}