<?php

namespace App\Console\Commands;

use App\Models\Aluno;
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
        $name  = $this->ask('Nome');
        $email = $this->ask('Email');

        $role = $this->choice('Role', ['admin', 'professor', 'aluno'], 1); 

        $alunoId = null;
        if ($role === 'aluno') {
            $alunoId = $this->askAlunoId();
            if ($alunoId === false) {
                return self::FAILURE;
            }
        }

        $password = $this->secret('Senha');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'unique:users,email'],
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
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => $role,
            'aluno_id' => $alunoId,
        ]);

        $this->info('Usuário criado com sucesso!');
        $this->line("  ID:    {$user->id}");
        $this->line("  Nome:  {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Role:  {$user->role}");

        if ($user->aluno_id) {
            $this->line("  Aluno vinculado: #{$user->aluno_id}");
        }

        return self::SUCCESS;
    }

    private function askAlunoId(): int|false
    {
        $idInput = $this->ask('ID do aluno a vincular (deixe em branco para buscar por nome)');

        if ($idInput) {
            $aluno = Aluno::find($idInput);
            if (! $aluno) {
                $this->error("Aluno com ID {$idInput} não encontrado.");
                return false;
            }
            $this->line("  → Vinculando a: {$aluno->nome}");
            return $aluno->id;
        }

        $nome = $this->ask('Nome do aluno (busca parcial)');
        $alunos = Aluno::where('nome', 'ilike', "%{$nome}%")->get(['id', 'nome']);

        if ($alunos->isEmpty()) {
            $this->error("Nenhum aluno encontrado com nome '{$nome}'.");
            return false;
        }

        $opcoes = $alunos->mapWithKeys(fn ($a) => [$a->id => "{$a->id} — {$a->nome}"])->toArray();
        $escolha = $this->choice('Selecione o aluno', $opcoes);

        return (int) explode(' —', $escolha)[0];
    }
}