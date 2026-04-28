<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateUser extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Cria um novo usuário do sistema interativamente';

    public function handle(): int
    {
        $name = $this->ask('Nome');
        $email = $this->ask('Email');
        $password = $this->secret('Senha');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', Password::min(8)->letters()->numbers()],
            ]
        );

        if ($validator->fails()) {
            $this->error('Erro de validação:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Usuário criado com sucesso!");
        $this->line("  ID: {$user->id}");
        $this->line("  Nome: {$user->name}");
        $this->line("  Email: {$user->email}");

        return self::SUCCESS;
    }
}