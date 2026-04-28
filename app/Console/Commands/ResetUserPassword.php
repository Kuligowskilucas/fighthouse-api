<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email}';

    protected $description = 'Reseta a senha de um usuário (operação manual em caso de senha esquecida)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Usuário com email '{$email}' não encontrado.");
            return self::FAILURE;
        }

        $this->info("Resetando senha para: {$user->name} ({$user->email})");

        if (! $this->confirm('Confirma a operação?', false)) {
            $this->line('Operação cancelada.');
            return self::SUCCESS;
        }

        $password = $this->secret('Nova senha');

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::min(8)->letters()->numbers()]]
        );

        if ($validator->fails()) {
            $this->error('Erro de validação:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }
            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($password)]);

        $tokensDeletados = $user->tokens()->delete();

        $this->info('Senha resetada com sucesso!');
        $this->line("  {$tokensDeletados} token(s) invalidado(s).");
        $this->line("Avise o usuário para fazer login novamente com a nova senha.");

        return self::SUCCESS;
    }
}