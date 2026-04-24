<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlanoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\MensalidadeController;

// Rotas públicas
Route::post('login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::apiResource('planos', PlanoController::class);
    Route::apiResource('alunos', AlunoController::class);
    Route::apiResource('mensalidades', MensalidadeController::class);

    // Ações específicas
    Route::post('mensalidades/{mensalidade}/marcar-pagamento', [MensalidadeController::class, 'marcarPagamento']);
    Route::post('mensalidades/{mensalidade}/desfazer-pagamento', [MensalidadeController::class, 'desfazerPagamento']);
});