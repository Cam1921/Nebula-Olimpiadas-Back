<?php

use App\Http\Controllers\ListaCompetidoresController;
use App\Http\Controllers\CompetidorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::get('/health', fn() => response()->json(['ok' => true]));

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::post('/users/import', [ListaCompetidoresController::class, 'import']);

Route::get('/competidores', [ListaCompetidoresController::class, 'index']);

Route::prefix('competidores')->group(function () {
    Route::get('/listar', [CompetidorController::class, 'listar']);
});
