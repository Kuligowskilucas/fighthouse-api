<?php

namespace Tests\Feature\Planos;

use App\Models\Aluno;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
    }

    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------

    public function test_lista_planos(): void
    {
        Plano::factory()->count(3)->create();

        $response = $this->getJson('/api/planos');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_lista_planos_inclui_horarios(): void
    {
        $plano = Plano::factory()->create();
        $plano->horarios()->createMany([
            ['horario' => '08:00'],
            ['horario' => '18:00'],
        ]);

        $response = $this->getJson('/api/planos');

        $response->assertOk();

        $planoData = collect($response->json('data'))->firstWhere('id', $plano->id);

        $this->assertNotNull($planoData);
        $this->assertEqualsCanonicalizing(['08:00', '18:00'], $planoData['horarios']);
    }


    public function test_lista_planos_inclui_contagem_de_alunos(): void
    {
        $plano = Plano::factory()->create();
        Aluno::factory()->count(4)->create(['plano_id' => $plano->id]);

        $response = $this->getJson('/api/planos');

        $planoData = collect($response->json('data'))->firstWhere('id', $plano->id);

        $this->assertSame(4, $planoData['alunos_count']);
    }

    // -------------------------------------------------------------------------
    // SHOW
    // -------------------------------------------------------------------------

    public function test_exibe_plano(): void
    {
        $plano = Plano::factory()->create([
            'nome'        => 'Boxe',
            'valor'       => 129.90,
            'dias_semana' => 'Ter/Qui',
        ]);

        $response = $this->getJson("/api/planos/{$plano->id}");

        $response->assertOk()
            ->assertJsonPath('data.nome', 'Boxe')
            ->assertJsonPath('data.valor', 129.90)
            ->assertJsonPath('data.dias_semana', 'Ter/Qui')
            ->assertJsonPath('data.horarios', []);
    }

    public function test_retorna_404_para_plano_inexistente(): void
    {
        $this->getJson('/api/planos/999999')->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    public function test_cria_plano_com_dias_semana_e_horarios(): void
    {
        $response = $this->postJson('/api/planos', [
            'nome'        => 'Muay Thai',
            'valor'       => 150.00,
            'ativo'       => true,
            'dias_semana' => 'Seg/Qua/Sex',
            'horarios'    => ['06:00', '18:00', '20:00'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.dias_semana', 'Seg/Qua/Sex')
            ->assertJsonPath('data.horarios', ['06:00', '18:00', '20:00']);

        $this->assertDatabaseHas('planos', ['nome' => 'Muay Thai', 'dias_semana' => 'Seg/Qua/Sex']);
        $this->assertDatabaseCount('horarios_plano', 3);
    }

    public function test_nome_e_obrigatorio(): void
    {
        $response = $this->postJson('/api/planos', [
            'valor' => 100.00,
            'ativo' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nome']);
    }

    public function test_valor_e_obrigatorio(): void
    {
        $response = $this->postJson('/api/planos', [
            'nome'  => 'Turma Teste',
            'ativo' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['valor']);
    }

    public function test_horario_com_formato_invalido_e_rejeitado(): void
    {
        $response = $this->postJson('/api/planos', [
            'nome'     => 'Turma Teste',
            'valor'    => 100.00,
            'ativo'    => true,
            'horarios' => ['8:00', '18h00', 'meio-dia'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['horarios.0', 'horarios.1', 'horarios.2']);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    public function test_atualiza_sincroniza_horarios(): void
    {
        $plano = Plano::factory()->create();
        $plano->horarios()->createMany([
            ['horario' => '08:00'],
            ['horario' => '18:00'],
        ]);

        $response = $this->putJson("/api/planos/{$plano->id}", [
            'nome'     => $plano->nome,
            'valor'    => $plano->valor,
            'ativo'    => true,
            'horarios' => ['20:00'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.horarios', ['20:00']);

        // Os dois horários antigos foram removidos, apenas o novo persiste
        $this->assertDatabaseCount('horarios_plano', 1);
        $this->assertDatabaseHas('horarios_plano', [
            'plano_id' => $plano->id,
            'horario'  => '20:00',
        ]);
    }

    public function test_atualiza_com_array_vazio_remove_todos_horarios(): void
    {
        $plano = Plano::factory()->create();
        $plano->horarios()->createMany([
            ['horario' => '08:00'],
            ['horario' => '18:00'],
        ]);

        $response = $this->putJson("/api/planos/{$plano->id}", [
            'nome'     => $plano->nome,
            'valor'    => $plano->valor,
            'ativo'    => true,
            'horarios' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.horarios', []);

        $this->assertDatabaseCount('horarios_plano', 0);
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------

    public function test_deleta_plano_sem_alunos(): void
    {
        $plano = Plano::factory()->create();

        $response = $this->deleteJson("/api/planos/{$plano->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('planos', ['id' => $plano->id]);
    }

    public function test_nao_deleta_plano_com_alunos_vinculados(): void
    {
        $plano = Plano::factory()->create();
        Aluno::factory()->create(['plano_id' => $plano->id]);

        $response = $this->deleteJson("/api/planos/{$plano->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Não é possível excluir uma turma com alunos vinculados.');

        $this->assertDatabaseHas('planos', ['id' => $plano->id]);
    }
}