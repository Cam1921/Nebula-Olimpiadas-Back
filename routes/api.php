<?php

use App\Http\Controllers\ImportacionesController;
use App\Http\Controllers\ListaCompetidoresController;
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

Route::prefix('importaciones')->group(function () {
    Route::post('/preview', [ImportacionesController::class, 'preview']);     // Valida CSV sin guardar
    Route::post('/confirmar', [ImportacionesController::class, 'confirmar']); // Guarda en BD
});

