<?php

namespace Database\Seeders;

use App\Models\Aluno;
use App\Models\Mensalidade;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Planos sempre rodam (dados reais da academia)
        $this->call(PlanoSeeder::class);

        // Em dev/teste, gera dados fake pra ter com o que trabalhar
        if (app()->environment('local', 'testing')) {
            User::factory()->create([
                'name' => 'Marquete',
                'email' => 'marquete@fighthouse.local',
                'password' => bcrypt('senha123'),
            ]);

            Aluno::factory(30)->create()->each(function ($aluno) {
            // Mensalidade do mês atual
            $mensalidadeAtual = Mensalidade::factory()->create(['aluno_id' => $aluno->id]);
            if (fake()->boolean(60)) {
                $mensalidadeAtual->update([
                    'data_pagamento' => now()->subDays(rand(1, 5)),
                    'forma_pagamento' => fake()->randomElement(['pix', 'dinheiro', 'cartao']),
                ]);
            }

            // 70% dos alunos têm mês anterior pago
            if (fake()->boolean(70)) {
                Mensalidade::factory()
                    ->paga()
                    ->create([
                        'aluno_id' => $aluno->id,
                        'mes_referencia' => now()->subMonth()->startOfMonth(),
                        'data_vencimento' => now()->subMonth()->day($aluno->dia_vencimento),
                    ]);
            } else {
                // 30% restantes: mês anterior vencido e NÃO pago (vira inadimplente)
                Mensalidade::factory()
                    ->create([
                        'aluno_id' => $aluno->id,
                        'mes_referencia' => now()->subMonth()->startOfMonth(),
                        'data_vencimento' => now()->subMonth()->day($aluno->dia_vencimento),
                    ]);
            }
        });
        }
    }
}