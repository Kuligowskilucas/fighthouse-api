<?php

namespace Tests\Feature\Mensalidade;

use App\Models\Aluno;
use Carbon\Carbon;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GerarMensalidadesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanoSeeder::class);
    }

    public function test_comando_gera_mensalidades_do_mes_atual(): void
    {
        Aluno::factory()->count(3)->create([
            'ativo' => true,
            'data_matricula' => Carbon::now()->subYear(),
        ]);

        $this->artisan('mensalidades:gerar')
            ->expectsOutputToContain('Gerando mensalidades para:')
            ->expectsOutputToContain('Mensalidades criadas: 3')
            ->expectsOutputToContain('Mensalidades ignoradas (já existiam): 0')
            ->assertSuccessful();

        $this->assertDatabaseCount('mensalidades', 3);
    }

    public function test_comando_aceita_mes_via_option(): void
    {
        Aluno::factory()->create([
            'ativo' => true,
            'data_matricula' => Carbon::create(2026, 1, 1),
        ]);

        $this->artisan('mensalidades:gerar --mes=2026-06')
            ->expectsOutputToContain('06/2026')
            ->assertSuccessful();

        $this->assertDatabaseHas('mensalidades', [
            'mes_referencia' => '2026-06-01',
        ]);
    }

    public function test_comando_falha_com_formato_invalido(): void
    {
        $this->artisan('mensalidades:gerar --mes=invalido')
            ->expectsOutput('Formato de mês inválido. Use YYYY-MM (ex: 2026-11).')
            ->assertFailed();

        $this->assertDatabaseCount('mensalidades', 0);
    }

    public function test_comando_e_idempotente(): void
    {
        Aluno::factory()->count(2)->create([
            'ativo' => true,
            'data_matricula' => Carbon::now()->subYear(),
        ]);

        $this->artisan('mensalidades:gerar')->assertSuccessful();

        $this->artisan('mensalidades:gerar')
            ->expectsOutputToContain('Mensalidades criadas: 0')
            ->expectsOutputToContain('Mensalidades ignoradas (já existiam): 2')
            ->assertSuccessful();

        $this->assertDatabaseCount('mensalidades', 2);
    }
}