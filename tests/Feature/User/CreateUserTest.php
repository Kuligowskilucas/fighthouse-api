<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_cria_usuario_com_dados_validos(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Recepção',
            'email' => 'recepcao@fighthouse.local',
            'password' => 'senha1234',
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'Recepção',
                    'email' => 'recepcao@fighthouse.local',
                ],
            ])
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'email' => 'recepcao@fighthouse.local',
        ]);

        $usuarioCriado = User::where('email', 'recepcao@fighthouse.local')->first();
        $this->assertTrue(Hash::check('senha1234', $usuarioCriado->password));
    }

    public function test_senha_fraca_retorna_422(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Fulano',
            'email' => 'fulano@example.com',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_email_duplicado_retorna_422(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $response = $this->postJson('/api/users', [
            'name' => 'Outro',
            'email' => 'existente@example.com',
            'password' => 'senha1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_sem_token_retorna_401(): void
    {
        $this->app['auth']->forgetGuards();

        $response = $this->postJson('/api/users', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'senha1234',
        ]);

        $response->assertUnauthorized();
    }
}