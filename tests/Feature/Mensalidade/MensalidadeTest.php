<?php

namespace Tests\Feature\Mensalidade;

use App\Models\Aluno;
use App\Models\Mensalidade;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MensalidadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanoSeeder::class);
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_marca_pagamento_com_sucesso(): void
    {
        $mensalidade = Mensalidade::factory()->create();

        $response = $this->postJson(
            "/api/mensalidades/{$mensalidade->id}/marcar-pagamento",
            ['forma_pagamento' => 'pix']
        );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $mensalidade->id,
                    'forma_pagamento' => 'pix',
                    'status' => 'paga',
                ],
            ]);

        $this->assertDatabaseHas('mensalidades', [
            'id' => $mensalidade->id,
            'forma_pagamento' => 'pix',
        ]);

        $this->assertNotNull($mensalidade->fresh()->data_pagamento);
    }

    public function test_marcar_pagamento_de_mensalidade_ja_paga_retorna_409(): void
    {
        $mensalidade = Mensalidade::factory()->paga()->create();

        $response = $this->postJson(
            "/api/mensalidades/{$mensalidade->id}/marcar-pagamento",
            ['forma_pagamento' => 'dinheiro']
        );

        $response->assertStatus(409);
    }

    public function test_filtro_por_status_atrasada_funciona(): void
    {
        $aluno = Aluno::factory()->create();

        Mensalidade::factory()->paga()->create([
            'aluno_id' => $aluno->id,
            'mes_referencia' => Carbon::now()->subMonths(2)->startOfMonth(),
            'data_vencimento' => Carbon::now()->subMonths(2)->day(10),
        ]);

        Mensalidade::factory()->create([
            'aluno_id' => $aluno->id,
            'mes_referencia' => Carbon::now()->subMonth()->startOfMonth(),
            'data_vencimento' => Carbon::now()->subMonth()->day(10),
            'data_pagamento' => null,
        ]);

        Mensalidade::factory()->create([
            'aluno_id' => $aluno->id,
            'mes_referencia' => Carbon::now()->startOfMonth(),
            'data_vencimento' => Carbon::now()->addDays(5),
            'data_pagamento' => null,
        ]);

        $response = $this->getJson('/api/mensalidades?status=atrasada');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'atrasada');
    }
}