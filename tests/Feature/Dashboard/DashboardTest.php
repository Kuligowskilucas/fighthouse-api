<?php

namespace Tests\Feature\Dashboard;

use App\Models\Aluno;
use App\Models\Mensalidade;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanoSeeder::class);
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_resumo_mensal_retorna_estrutura_e_calculos_corretos(): void
    {
        $aluno = Aluno::factory()->create(['ativo' => true]);

        // Mensalidade do mês atual, paga (R$ 100)
        Mensalidade::factory()->paga()->create([
            'aluno_id' => $aluno->id,
            'mes_referencia' => Carbon::now()->startOfMonth(),
            'data_vencimento' => Carbon::now()->startOfMonth()->day(10),
            'valor' => 100,
        ]);

        // Mensalidade do mês atual, em aberto (R$ 200)
        Mensalidade::factory()->create([
            'aluno_id' => Aluno::factory()->create()->id,
            'mes_referencia' => Carbon::now()->startOfMonth(),
            'data_vencimento' => Carbon::now()->addDays(5),
            'data_pagamento' => null,
            'valor' => 200,
        ]);

        $response = $this->getJson('/api/dashboard/resumo');

        $response->assertOk()
            ->assertJsonStructure([
                'mes_referencia',
                'alunos_ativos',
                'mensalidades_do_mes' => ['total', 'pagas', 'em_aberto'],
                'financeiro' => [
                    'recebido_no_mes',
                    'a_receber_no_mes',
                    'total_atrasado_geral',
                ],
                'inadimplencia' => ['quantidade_atrasadas', 'alunos_inadimplentes'],
            ])
            ->assertJson([
                'mensalidades_do_mes' => [
                    'total' => 2,
                    'pagas' => 1,
                    'em_aberto' => 1,
                ],
                'financeiro' => [
                    'recebido_no_mes' => 100,
                    'a_receber_no_mes' => 200,
                ],
            ]);
    }
}