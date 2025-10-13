<?php

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\EvaluacionesController;
use App\Http\Controllers\ImportacionesController;
use App\Http\Controllers\ListarInscritosCotroller;
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
    Route::post('/preview', [ImportacionesController::class, 'preview']);
    Route::post('/confirmar', [ImportacionesController::class, 'confirmar']);
    Route::get('/errores', [ImportacionesController::class, 'descargarErrores']);
});

Route::prefix('competidores')->group(function () {
    Route::get('/listar', [ListarInscritosCotroller::class, 'listar']);
    Route::get('/exportar', [ListarInscritosCotroller::class, 'exportar']);
});

Route::prefix('catalogos')->group(function () {
    Route::get('/areas', [CatalogoController::class, 'areas']);
    Route::get('/niveles', [CatalogoController::class, 'niveles']);
    Route::get('/', [CatalogoController::class, 'catalogos']);
});
Route::prefix('evaluaciones')->group(function () {
    Route::get('evaluadores/{id}', [EvaluacionesController::class, 'indexByEvaluador']);
});