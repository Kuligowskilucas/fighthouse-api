<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role'     => ['sometimes', 'string', 'in:admin,professor,aluno'],
            'aluno_id' => [
                'required_if:role,aluno',   
                'prohibited_if:role,admin', 
                'prohibited_if:role,professor',
                'nullable',
                'integer',
                'exists:alunos,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'              => 'Já existe um usuário com esse email.',
            'aluno_id.required_if'      => 'É obrigatório vincular um aluno quando o role é aluno.',
            'aluno_id.prohibited_if'    => 'Admin e professor não podem ser vinculados a um aluno.',
            'aluno_id.exists'           => 'O aluno selecionado não existe.',
        ];
    }
}