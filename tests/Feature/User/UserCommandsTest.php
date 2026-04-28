<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_create_cria_usuario_com_dados_validos(): void
    {
        $this->artisan('user:create')
            ->expectsQuestion('Nome', 'João Silva')
            ->expectsQuestion('Email', 'joao@example.com')
            ->expectsQuestion('Senha', 'senha1234')
            ->expectsOutput('Usuário criado com sucesso!')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $user = User::where('email', 'joao@example.com')->first();
        $this->assertTrue(Hash::check('senha1234', $user->password));
    }

    public function test_user_create_falha_com_senha_fraca(): void
    {
        $this->artisan('user:create')
            ->expectsQuestion('Nome', 'João Silva')
            ->expectsQuestion('Email', 'joao@example.com')
            ->expectsQuestion('Senha', '123')
            ->expectsOutput('Erro de validação:')
            ->assertFailed();

        $this->assertDatabaseMissing('users', [
            'email' => 'joao@example.com',
        ]);
    }

    public function test_user_create_falha_com_email_duplicado(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $this->artisan('user:create')
            ->expectsQuestion('Nome', 'Outro')
            ->expectsQuestion('Email', 'existente@example.com')
            ->expectsQuestion('Senha', 'senha1234')
            ->expectsOutput('Erro de validação:')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_reset_password_atualiza_senha_e_invalida_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'marquete@fighthouse.local',
            'password' => Hash::make('senhaantiga'),
        ]);

        $user->createToken('dispositivo1');
        $user->createToken('dispositivo2');

        $this->assertEquals(2, $user->tokens()->count());

        $this->artisan('user:reset-password marquete@fighthouse.local')
            ->expectsConfirmation('Confirma a operação?', 'yes')
            ->expectsQuestion('Nova senha', 'novasenha456')
            ->expectsOutput('Senha resetada com sucesso!')
            ->assertSuccessful();

        $user->refresh();

        $this->assertTrue(Hash::check('novasenha456', $user->password));
        $this->assertFalse(Hash::check('senhaantiga', $user->password));
        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_reset_password_falha_com_email_inexistente(): void
    {
        $this->artisan('user:reset-password naoexiste@example.com')
            ->expectsOutput("Usuário com email 'naoexiste@example.com' não encontrado.")
            ->assertFailed();
    }

    public function test_reset_password_pode_ser_cancelado(): void
    {
        $user = User::factory()->create([
            'email' => 'marquete@fighthouse.local',
            'password' => Hash::make('senhaoriginal'),
        ]);

        $this->artisan('user:reset-password marquete@fighthouse.local')
            ->expectsConfirmation('Confirma a operação?', 'no')
            ->expectsOutput('Operação cancelada.')
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue(Hash::check('senhaoriginal', $user->password));
    }
}