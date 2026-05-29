<?php

namespace Database\Factories;

use App\Models\Plano;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plano>
 */
class PlanoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome'               => $this->faker->word(),
            'valor'              => $this->faker->randomFloat(2, 80, 300),
            'frequencia_semanal' => $this->faker->randomElement([2, 3, null]),
            'ativo'              => true,
            'dias_semana'        => null,
        ];
    }
}
