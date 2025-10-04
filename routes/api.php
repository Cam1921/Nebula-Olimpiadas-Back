<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResponsableAcademicoController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/responsables', [ResponsableAcademicoController::class, 'store']);

Route::get('/health', fn() => response()->json(['ok' => true]));

Route::post('/login', [AuthController::class, 'login']);
Route::get('/responsables', [ResponsableAcademicoController::class, 'index']);

Route::get('/areas/disponibles', [ResponsableAcademicoController::class, 'areasDisponibles']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
