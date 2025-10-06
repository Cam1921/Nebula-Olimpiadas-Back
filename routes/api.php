<?php

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ImportacionesController;
use App\Http\Controllers\ListaCompetidoresController;
use App\Http\Controllers\ListarInscritosCotroller;
use App\Services\ListarCompetidoresService;
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

//Rutas para el proceso de importacion

Route::prefix('importaciones')->group(function () {
    // Valida CSV y guarda temporalmente filas válidas/errores en Redis
    Route::post('/preview', [ImportacionesController::class, 'preview']);

    // Confirma la importación usando import_id
    Route::post('/confirmar', [ImportacionesController::class, 'confirmar']);

    // Descarga CSV con errores usando import_id
    Route::get('/errores', [ImportacionesController::class, 'descargarErrores']);
});

//Rutas para la organización de los competidores

Route::prefix('competidores')->group(function () {
    Route::get('/listar', [ListarInscritosCotroller::class, 'listar']);
});

Route::prefix('catalogos')->group(function () {
    Route::get('/areas', [CatalogoController::class, 'areas']);
    Route::get('/niveles', [CatalogoController::class, 'niveles']);
    Route::get('/', [CatalogoController::class, 'catalogos']); // retorna todo junto
});