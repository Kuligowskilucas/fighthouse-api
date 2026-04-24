<?php

namespace App\Http\Requests\Plano;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', 'unique:planos,nome'],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'frequencia_semanal' => ['nullable', 'integer', 'min:1', 'max:7'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe um plano com esse nome.',
            'valor.max' => 'O valor não pode ser maior que R$ 9.999,99.',
            'frequencia_semanal.max' => 'A frequência semanal não pode ser maior que 7.',
        ];
    }
}