<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResponsableAcademicoController;
use App\Http\Controllers\EvaluadorController; // 👈 ¡ESTA LÍNEA FALTABA!

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/health', fn() => response()->json(['ok' => true]));

// ✅ Rutas públicas para responsable académico
Route::post('/responsable-academico', [ResponsableAcademicoController::class, 'store']);
Route::get('/responsable-academico', [ResponsableAcademicoController::class, 'index']);
Route::get('/responsable-academico/check', [ResponsableAcademicoController::class, 'check']);
Route::put('/responsable-academico/{id}', [ResponsableAcademicoController::class, 'update']);
    Route::delete('/responsable-academico/{id}', [ResponsableAcademicoController::class, 'destroy']);


// ✅ Rutas públicas para evaluador
Route::post('/evaluador', [EvaluadorController::class, 'store']);
Route::get('/evaluador', [EvaluadorController::class, 'index']);
Route::get('/evaluador/check', [EvaluadorController::class, 'check']);
Route::put('/evaluador/{id}', [EvaluadorController::class, 'update']);      // ← MOVIDO FUERA
Route::delete('/evaluador/{id}', [EvaluadorController::class, 'destroy']); // ← MOVIDO FUERA

/*
// Rutas protegidas (solo para usuarios logueados)
  Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Responsables
    Route::put('/responsable-academico/{id}', [ResponsableAcademicoController::class, 'update']);
    Route::delete('/responsable-academico/{id}', [ResponsableAcademicoController::class, 'destroy']);

    // Evaluadores
    Route::put('/evaluador/{id}', [EvaluadorController::class, 'update']);
    Route::delete('/evaluador/{id}', [EvaluadorController::class, 'destroy']);
});  
 */