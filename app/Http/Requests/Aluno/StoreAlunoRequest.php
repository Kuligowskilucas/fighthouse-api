<?php

namespace App\Http\Requests\Aluno;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlunoRequest extends FormRequest
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
        return [
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['required', 'string', 'regex:/^\d{10,13}$/', 'unique:alunos,telefone'],
            'email' => ['nullable', 'email', 'max:255'],
            'plano_id' => ['required', 'integer', 'exists:planos,id'],
            'valor_personalizado' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'dia_vencimento' => ['required', 'integer', 'min:1', 'max:31'],
            'data_matricula' => ['required', 'date', 'before_or_equal:today'],
            'ativo' => ['sometimes', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'telefone.regex' => 'Telefone deve conter entre 10 e 13 dígitos (só números).',
            'telefone.unique' => 'Já existe um aluno com esse telefone.',
            'plano_id.exists' => 'O plano selecionado não existe.',
            'dia_vencimento.max' => 'O dia de vencimento deve estar entre 1 e 31.',
            'data_matricula.before_or_equal' => 'A data de matrícula não pode ser no futuro.',
        ];
    }
}