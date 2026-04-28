<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlanoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\MensalidadeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;

// Rotas públicas
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('logout-all', [AuthController::class, 'logoutAll']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('me/change-password', [UserController::class, 'changePassword']);

    Route::post('users', [UserController::class, 'store']);

    Route::apiResource('planos', PlanoController::class);
    Route::apiResource('alunos', AlunoController::class);
    Route::apiResource('mensalidades', MensalidadeController::class);

    Route::post('mensalidades/{mensalidade}/marcar-pagamento', [MensalidadeController::class, 'marcarPagamento']);
    Route::post('mensalidades/{mensalidade}/desfazer-pagamento', [MensalidadeController::class, 'desfazerPagamento']);

    Route::get('dashboard/resumo', [DashboardController::class, 'resumoMensal']);
    Route::get('dashboard/inadimplentes', [DashboardController::class, 'inadimplentes']);
});