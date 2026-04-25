<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_com_credenciais_validas_retorna_token(): void
    {
        $user = User::factory()->create([
            'email' => 'marquete@fighthouse.local',
            'password' => bcrypt('senha123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'marquete@fighthouse.local',
            'password' => 'senha123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => 'marquete@fighthouse.local',
                ],
            ]);
    }

    public function test_login_com_credenciais_invalidas_retorna_422(): void
    {
        User::factory()->create([
            'email' => 'marquete@fighthouse.local',
            'password' => bcrypt('senha123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'marquete@fighthouse.local',
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_rota_protegida_sem_token_retorna_401(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }
}