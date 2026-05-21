<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            'email'    => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role'     => 'admin', 
            'aluno_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function professor(): static
    {
        return $this->state(['role' => 'professor']);
    }

    public function aluno(): static
    {
        return $this->state(['role' => 'aluno']);
    }
}