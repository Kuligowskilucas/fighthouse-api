<?php

namespace App\Http\Requests\Plano;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planoId = $this->route('plano')->id;

        return [
            'nome' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('planos', 'nome')->ignore($planoId),
            ],
            'valor' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999.99'],
            'frequencia_semanal' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:7'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}