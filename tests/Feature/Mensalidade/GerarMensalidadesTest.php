<?php

namespace Tests\Feature\Mensalidade;

use App\Models\Aluno;
use App\Models\Mensalidade;
use App\Models\Plano;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GerarMensalidadesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanoSeeder::class);
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_gera_mensalidades_para_alunos_ativos(): void
    {
        Aluno::factory()->count(3)->create([
            'ativo' => true,
            'data_matricula' => Carbon::now()->subYear(),
        ]);

        $response = $this->postJson('/api/mensalidades/gerar', [
            'mes_referencia' => Carbon::now()->format('Y-m'),
        ]);

        $response->assertOk()
            ->assertJson([
                'criadas' => 3,
                'ignoradas' => 0,
            ]);

        $this->assertDatabaseCount('mensalidades', 3);
    }

    public function test_pula_alunos_inativos(): void
    {
        Aluno::factory()->create([
            'ativo' => true,
            'data_matricula' => Carbon::now()->subYear(),
        ]);
        Aluno::factory()->create([
            'ativo' => false,
            'data_matricula' => Carbon::now()->subYear(),
        ]);

        $response = $this->postJson('/api/mensalidades/gerar');

        $response->assertOk()
            ->assertJson(['criadas' => 1]);

        $this->assertDatabaseCount('mensalidades', 1);
    }

    public function test_pula_alunos_matriculados_apos_o_mes_de_referencia(): void
    {
        // Aluno antigo: deve gerar
        Aluno::factory()->create([
            'ativo' => true,
            'data_matricula' => Carbon::now()->subYear(),
        ]);

        // Aluno novo: data_matricula posterior ao mês passado, não deve gerar
        Aluno::factory()->create([
            'ativo' => true,
            'data_matricula' => Carbon::now()->startOfMonth(),
        ]);

        $mesPassado = Carbon::now()->subMonth()->format('Y-m');

        $response = $this->postJson('/api/mensalidades/gerar', [
            'mes_referencia' => $mesPassado,
        ]);

        $response->assertOk()
            ->assertJson(['criadas' => 1]);
    }

    public function test_idempotencia_nao_duplica_mensalidades(): void
    {
        Aluno::factory()->count(2)->create([
            'ativo' => true,
            'data_matricula' => Carbon::now()->subYear(),
        ]);

        $this->postJson('/api/mensalidades/gerar')->assertOk();
        $this->assertDatabaseCount('mensalidades', 2);

        $response = $this->postJson('/api/mensalidades/gerar');

        $response->assertOk()
            ->assertJson([
                'criadas' => 0,
                'ignoradas' => 2,
            ]);

        $this->assertDatabaseCount('mensalidades', 2);
    }

    public function test_usa_valor_personalizado_quando_existe(): void
    {
        $plano = Plano::first();

        Aluno::factory()->create([
            'ativo' => true,
            'plano_id' => $plano->id,
            'valor_personalizado' => 99.50,
            'data_matricula' => Carbon::now()->subYear(),
        ]);

        $this->postJson('/api/mensalidades/gerar')->assertOk();

        $this->assertDatabaseHas('mensalidades', [
            'valor' => 99.50,
        ]);
    }

    public function test_ajusta_dia_de_vencimento_para_fim_de_mes_quando_dia_nao_existe(): void
    {
        Aluno::factory()->create([
            'ativo' => true,
            'dia_vencimento' => 31,
            'data_matricula' => Carbon::create(2026, 1, 1),
        ]);

        // Fevereiro de 2026 tem 28 dias, então dia 31 deve virar dia 28
        $response = $this->postJson('/api/mensalidades/gerar', [
            'mes_referencia' => '2026-02',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('mensalidades', [
            'data_vencimento' => '2026-02-28',
        ]);
    }

    public function test_formato_invalido_retorna_422(): void
    {
        $response = $this->postJson('/api/mensalidades/gerar', [
            'mes_referencia' => 'janeiro',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mes_referencia']);
    }

    public function test_sem_token_retorna_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->postJson('/api/mensalidades/gerar');

        $response->assertUnauthorized();
    }
}