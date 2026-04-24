<?php

namespace Database\Factories;

use App\Models\Aluno;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class MensalidadeFactory extends Factory
{
    public function definition(): array
    {
        $mesReferencia = Carbon::now()->startOfMonth();

        return [
            'aluno_id' => Aluno::factory(),
            'mes_referencia' => $mesReferencia,
            'valor' => fake()->randomElement([129.90, 169.90, 249.90]),
            'data_vencimento' => $mesReferencia->copy()->day(fake()->numberBetween(5, 20)),
            'data_pagamento' => null,
            'forma_pagamento' => null,
        ];
    }

    public function paga(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'data_pagamento' => Carbon::parse($attributes['data_vencimento'])
                    ->subDays(fake()->numberBetween(0, 5)),
                'forma_pagamento' => fake()->randomElement(['pix', 'dinheiro', 'cartao']),
            ];
        });
    }

    public function atrasada(): static
    {
        return $this->state(fn () => [
            'mes_referencia' => Carbon::now()->subMonth()->startOfMonth(),
            'data_vencimento' => Carbon::now()->subMonth()->day(10),
            'data_pagamento' => null,
        ]);
    }
}