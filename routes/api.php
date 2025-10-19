<?php

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ControlFaseController;
use App\Http\Controllers\EvaluacionesController;
use App\Http\Controllers\ImportacionesController;
use App\Http\Controllers\ListarInscritosCotroller;
use App\Http\Controllers\PersonaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResponsableAcademicoController;
use App\Http\Controllers\EvaluadorController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


// Rutas protegidas (solo para usuarios logueados)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/persona', [PersonaController::class, 'show']);
    Route::put('/persona', [PersonaController::class, 'update']);
});

Route::prefix('catalogos')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/areas', [CatalogoController::class, 'areas']);
    Route::get('/niveles', [CatalogoController::class, 'niveles']);
    Route::get('/area-niveles', [CatalogoController::class, 'areaNiveles']);
    Route::get('/', [CatalogoController::class, 'catalogos']);
});

Route::middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    Route::post('/responsable-academico', [ResponsableAcademicoController::class, 'store']);
    Route::get('/responsable-academico', [ResponsableAcademicoController::class, 'index']);
    Route::get('/responsable-academico/check', [ResponsableAcademicoController::class, 'check']);
    Route::put('/responsable-academico/{id}', [ResponsableAcademicoController::class, 'update']);
    Route::delete('/responsable-academico/{id}', [ResponsableAcademicoController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    Route::post('/evaluador', [EvaluadorController::class, 'store']);
    Route::get('/evaluador', [EvaluadorController::class, 'index']);
    Route::get('/evaluador/check', [EvaluadorController::class, 'check']);
    Route::put('/evaluador/{id}', [EvaluadorController::class, 'update']);
    Route::delete('/evaluador/{id}', [EvaluadorController::class, 'destroy']);
});

Route::prefix('fases')->group(function () {
    Route::get('/', [ControlFaseController::class, 'index']);
    Route::post('/', [ControlFaseController::class, 'store']);
    Route::put('/{id}', [ControlFaseController::class, 'update']);
    Route::delete('/{id}', [ControlFaseController::class, 'destroy']);
});

Route::prefix('importaciones')->middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    Route::post('/preview', [ImportacionesController::class, 'preview']);
    Route::post('/confirmar', [ImportacionesController::class, 'confirmar']);
    Route::get('/errores', [ImportacionesController::class, 'descargarErrores']);
});

Route::prefix('competidores')->middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    Route::get('/listar', [ListarInscritosCotroller::class, 'listar']);
    Route::get('/exportar', [ListarInscritosCotroller::class, 'exportar']);
});

Route::prefix('evaluador')->middleware(['auth:sanctum', 'role:evaluador'])->group(function () {
    Route::get('/evaluaciones', [EvaluacionesController::class, 'index']);
    Route::put('/evaluaciones/{id}', [EvaluacionesController::class, 'update']);

});





