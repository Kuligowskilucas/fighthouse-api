<?php

use App\Http\Controllers\Api\PlanoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('planos', PlanoController::class);