<?php

namespace Tests\Feature\User;

use App\Models\Aluno;
use App\Models\Plano;
use App\Models\User;
use Database\Seeders\PlanoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanoSeeder::class);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin, 'sanctum');
    }

    public function test_admin_cria_professor_com_dados_validos(): void
    {
        $response = $this->postJson('/api/users', [
            'name'     => 'Professor João',
            'email'    => 'joao@fighthouse.local',
            'password' => 'senha1234',
            'role'     => 'professor',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'Professor João',
                    'role' => 'professor',
                    'aluno_id' => null,
                ],
            ])
            ->assertJsonMissingPath('data.password');
    }

    public function test_admin_cria_usuario_aluno_vinculado(): void
    {
        $aluno = Aluno::factory()->create();

        $response = $this->postJson('/api/users', [
            'name'     => $aluno->nome,
            'email'    => 'aluno@fighthouse.local',
            'password' => 'senha1234',
            'role'     => 'aluno',
            'aluno_id' => $aluno->id,
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'role'     => 'aluno',
                    'aluno_id' => $aluno->id,
                ],
            ]);
    }

    public function test_aluno_sem_aluno_id_retorna_422(): void
    {
        $response = $this->postJson('/api/users', [
            'name'     => 'Aluno Sem Vínculo',
            'email'    => 'semvinculo@fighthouse.local',
            'password' => 'senha1234',
            'role'     => 'aluno',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['aluno_id']);
    }

    public function test_professor_nao_pode_criar_usuario(): void
    {
        $professor = User::factory()->professor()->create();
        $this->actingAs($professor, 'sanctum');

        $response = $this->postJson('/api/users', [
            'name'     => 'Novo',
            'email'    => 'novo@fighthouse.local',
            'password' => 'senha1234',
        ]);

        $response->assertForbidden();
    }

    public function test_professor_nao_acessa_dashboard(): void
    {
        $professor = User::factory()->professor()->create();
        $this->actingAs($professor, 'sanctum');

        $this->getJson('/api/dashboard/resumo')->assertForbidden();
        $this->getJson('/api/dashboard/inadimplentes')->assertForbidden();
    }

    public function test_professor_acessa_alunos_e_mensalidades(): void
    {
        $professor = User::factory()->professor()->create();
        $this->actingAs($professor, 'sanctum');

        $this->getJson('/api/alunos')->assertOk();
        $this->getJson('/api/mensalidades')->assertOk();
    }

    public function test_professor_pode_criar_aluno(): void
    {
        $professor = User::factory()->professor()->create();
        $this->actingAs($professor, 'sanctum');

        $response = $this->postJson('/api/alunos', [
            'nome'           => 'Aluno Novo',
            'telefone'       => '5541999990000',
            'plano_id'       => Plano::first()->id,
            'dia_vencimento' => 10,
            'data_matricula' => '2026-01-01',
        ]);

        $response->assertCreated();
    }

    public function test_sem_token_retorna_401(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/users', [
            'name'  => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'senha1234',
        ])->assertUnauthorized();
    }

    public function test_senha_fraca_retorna_422(): void
    {
        $this->postJson('/api/users', [
            'name'     => 'Fulano',
            'email'    => 'fulano@example.com',
            'password' => '123',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_email_duplicado_retorna_422(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $this->postJson('/api/users', [
            'name'     => 'Outro',
            'email'    => 'existente@example.com',
            'password' => 'senha1234',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }
}