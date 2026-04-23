<?php

namespace Database\Factories;

use App\Models\Plano;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlunoFactory extends Factory
{
    public function definition(): array
    {
        $ddd = fake()->numberBetween(41, 48);
        $numero = fake()->numerify('9########');
        $dataMatricula = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'nome' => fake()->name(),
            'telefone' => "55{$ddd}{$numero}",
            'email' => fake()->optional(0.6)->safeEmail(),
            'plano_id' => Plano::inRandomOrder()->first()?->id ?? Plano::factory(),
            'valor_personalizado' => fake()->optional(0.1)->randomFloat(2, 80, 200),
            'dia_vencimento' => (int) $dataMatricula->format('d'),
            'data_matricula' => $dataMatricula,
            'ativo' => fake()->boolean(90),
            'observacoes' => fake()->optional(0.2)->sentence(),
        ];
    }
}