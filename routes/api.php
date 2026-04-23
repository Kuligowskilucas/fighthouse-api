<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlanoController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::apiResource('planos', PlanoController::class);
});