<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlanoController;
use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\MensalidadeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ─── Pública ──────────────────────────────────────────────────────────────────
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

// ─── Autenticado (todos os roles) ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('logout',      [AuthController::class, 'logout']);
    Route::post('logout-all',  [AuthController::class, 'logoutAll']);
    Route::get('me',           [AuthController::class, 'me']);
    Route::post('me/change-password', [UserController::class, 'changePassword']);

    // ─── Admin + Professor ─────────────────────────────────────────────────────
    Route::middleware('role:admin,professor')->group(function () {

        // Alunos — leitura e listagem
        Route::get('alunos',          [AlunoController::class, 'index']);
        Route::get('alunos/{aluno}',  [AlunoController::class, 'show']);

        // Mensalidades — leitura + ações de pagamento
        Route::get('mensalidades',                                          [MensalidadeController::class, 'index']);
        Route::get('mensalidades/{mensalidade}',                            [MensalidadeController::class, 'show']);
        Route::post('mensalidades/{mensalidade}/marcar-pagamento',          [MensalidadeController::class, 'marcarPagamento']);
        Route::post('mensalidades/{mensalidade}/desfazer-pagamento',        [MensalidadeController::class, 'desfazerPagamento']);

        // Planos — só leitura para professor
        Route::get('planos',         [PlanoController::class, 'index']);
        Route::get('planos/{plano}', [PlanoController::class, 'show']);
    });

    // ─── Apenas Admin ──────────────────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {

        // Alunos — escrita
        Route::post('alunos',           [AlunoController::class, 'store']);
        Route::put('alunos/{aluno}',    [AlunoController::class, 'update']);
        Route::patch('alunos/{aluno}',  [AlunoController::class, 'update']);
        Route::delete('alunos/{aluno}', [AlunoController::class, 'destroy']);

        // Mensalidades — geração
        Route::post('mensalidades/gerar', [MensalidadeController::class, 'gerar']);

        // Planos — escrita
        Route::post('planos',            [PlanoController::class, 'store']);
        Route::put('planos/{plano}',     [PlanoController::class, 'update']);
        Route::patch('planos/{plano}',   [PlanoController::class, 'update']);
        Route::delete('planos/{plano}',  [PlanoController::class, 'destroy']);

        // Usuários
        Route::get('users',  [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);

        // Dashboard
        Route::get('dashboard/resumo',        [DashboardController::class, 'resumoMensal']);
        Route::get('dashboard/inadimplentes', [DashboardController::class, 'inadimplentes']);
    });

    // ─── Apenas Aluno ──────────────────────────────────────────────────────────
    Route::middleware('role:aluno')->group(function () {
        // Aluno vê só as próprias informações
        Route::get('me/aluno', [UserController::class, 'meuAluno']);
    });
});