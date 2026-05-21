<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'     => ['sometimes', 'required', 'string', 'max:255'],
            'email'    => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'role'     => ['sometimes', 'required', 'string', 'in:admin,professor,aluno'],
            'aluno_id' => [
                'sometimes',
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
            'email.unique'           => 'Já existe um usuário com esse email.',
            'aluno_id.required_if'   => 'É obrigatório vincular um aluno quando o role é aluno.',
            'aluno_id.prohibited_if' => 'Admin e professor não podem ser vinculados a um aluno.',
        ];
    }
}