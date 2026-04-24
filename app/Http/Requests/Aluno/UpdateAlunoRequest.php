<?php

namespace App\Http\Requests\Aluno;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlunoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('telefone')) {
            $this->merge([
                'telefone' => preg_replace('/\D/', '', $this->telefone),
            ]);
        }
    }

    public function rules(): array
    {
        $alunoId = $this->route('aluno')->id;

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'telefone' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\d{10,13}$/',
                Rule::unique('alunos', 'telefone')->ignore($alunoId),
            ],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'plano_id' => ['sometimes', 'required', 'integer', 'exists:planos,id'],
            'valor_personalizado' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999.99'],
            'dia_vencimento' => ['sometimes', 'required', 'integer', 'min:1', 'max:31'],
            'data_matricula' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'ativo' => ['sometimes', 'boolean'],
            'observacoes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}