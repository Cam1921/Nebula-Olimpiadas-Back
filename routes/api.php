<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResponsableAcademicoController;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/health', fn() => response()->json(['ok' => true]));

// Rutas de responsable académico (públicas por ahora)
Route::post('/responsable-academico', [ResponsableAcademicoController::class, 'store']);
Route::get('/responsable-academico', [ResponsableAcademicoController::class, 'index']);
Route::get('/areas/disponibles', [ResponsableAcademicoController::class, 'areasDisponibles']);

// Rutas protegidas (requieren token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    
});