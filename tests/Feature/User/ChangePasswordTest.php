<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $tokenAtual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('senhaatual123'),
        ]);

        $this->tokenAtual = $this->user->createToken('test')->plainTextToken;
    }

    public function test_troca_senha_com_sucesso(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenAtual}")
            ->postJson('/api/me/change-password', [
                'current_password' => 'senhaatual123',
                'password' => 'novasenha456',
                'password_confirmation' => 'novasenha456',
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Senha alterada com sucesso. Outros dispositivos foram desconectados.']);

        $this->user->refresh();
        $this->assertTrue(Hash::check('novasenha456', $this->user->password));
        $this->assertFalse(Hash::check('senhaatual123', $this->user->password));
    }

    public function test_senha_atual_errada_retorna_422(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenAtual}")
            ->postJson('/api/me/change-password', [
                'current_password' => 'senha-errada',
                'password' => 'novasenha456',
                'password_confirmation' => 'novasenha456',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->user->refresh();
        $this->assertTrue(Hash::check('senhaatual123', $this->user->password));
    }

    public function test_senha_igual_atual_retorna_422(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenAtual}")
            ->postJson('/api/me/change-password', [
                'current_password' => 'senhaatual123',
                'password' => 'senhaatual123',
                'password_confirmation' => 'senhaatual123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_senha_fraca_retorna_422(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenAtual}")
            ->postJson('/api/me/change-password', [
                'current_password' => 'senhaatual123',
                'password' => '123',
                'password_confirmation' => '123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_troca_invalida_outros_tokens_mas_mantem_atual(): void
    {
        $this->user->createToken('celular');
        $this->user->createToken('tablet');

        $this->assertEquals(3, $this->user->tokens()->count());

        $this->withHeader('Authorization', "Bearer {$this->tokenAtual}")
            ->postJson('/api/me/change-password', [
                'current_password' => 'senhaatual123',
                'password' => 'novasenha456',
                'password_confirmation' => 'novasenha456',
            ])
            ->assertOk();
    
        $this->assertEquals(1, $this->user->tokens()->count());

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'test',
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'celular',
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'tablet',
        ]);
    }
}