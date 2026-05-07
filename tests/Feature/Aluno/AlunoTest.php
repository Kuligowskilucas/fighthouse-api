<?php

namespace Tests\Feature\Aluno;

use App\Models\Aluno;
use App\Models\Plano;
use App\Models\User;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlunoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanoSeeder::class);
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_cria_aluno_com_dados_validos(): void
    {
        $plano = Plano::first();

        $response = $this->postJson('/api/alunos', [
            'nome' => 'João Silva',
            'telefone' => '5541999998888',
            'email' => 'joao@example.com',
            'plano_id' => $plano->id,
            'dia_vencimento' => 10,
            'data_matricula' => '2026-01-15',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'nome' => 'João Silva',
                    'telefone' => '5541999998888',
                ],
            ]);

        $this->assertDatabaseHas('alunos', [
            'nome' => 'João Silva',
            'telefone' => '5541999998888',
        ]);
    }


    public function test_telefone_e_normalizado_na_criacao(): void
    {
        $response = $this->postJson('/api/alunos', [
            'nome' => 'João Silva',
            'telefone' => '+55 (41) 99999-8888',
            'plano_id' => Plano::first()->id,
            'dia_vencimento' => 10,
            'data_matricula' => '2026-01-15',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => ['telefone' => '5541999998888'],
            ]);

        $this->assertDatabaseHas('alunos', ['telefone' => '5541999998888']);
        $this->assertDatabaseMissing('alunos', ['telefone' => '+55 (41) 99999-8888']);
    }

    public function test_filtro_por_busca_funciona(): void
    {
        Aluno::factory()->create(['nome' => 'João Silva']);
        Aluno::factory()->create(['nome' => 'Maria Santos']);
        Aluno::factory()->create(['nome' => 'Pedro Oliveira']);

        $response = $this->getJson('/api/alunos?search=Maria');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Maria Santos');
    }

    public function test_aluno_criado_sem_ativo_retorna_ativo_true(): void
    {
        $response = $this->postJson('/api/alunos', [
            'nome' => 'João Silva',
            'telefone' => '5541999998888',
            'plano_id' => Plano::first()->id,
            'dia_vencimento' => 10,
            'data_matricula' => '2026-01-15',
        ]);
    
        $response->assertCreated()
            ->assertJson([
                'data' => ['ativo' => true],
            ]);
    }
}