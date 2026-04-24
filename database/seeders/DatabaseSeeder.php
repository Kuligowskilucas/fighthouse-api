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

            // 30 alunos com mensalidades variadas
            Aluno::factory(30)->create()->each(function ($aluno) {
                // Mensalidade do mês atual (ainda não paga)
                Mensalidade::factory()->create(['aluno_id' => $aluno->id]);

                // 70% dos alunos têm mês anterior pago
                if (fake()->boolean(70)) {
                    Mensalidade::factory()
                        ->paga()
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