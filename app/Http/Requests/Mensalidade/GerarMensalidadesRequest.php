<?php

namespace App\Http\Requests\Mensalidade;

use Illuminate\Foundation\Http\FormRequest;

class GerarMensalidadesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mes_referencia' => ['nullable', 'date_format:Y-m'],
        ];
    }

    public function messages(): array
    {
        return [
            'mes_referencia.date_format' => 'Mês de referência deve estar no formato YYYY-MM (ex: 2026-11).',
        ];
    }
}