<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResponsableAcademicoController;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/health', fn() => response()->json(['ok' => true]));

// ✅ Rutas públicas para responsable académico
Route::post('/responsable-academico', [ResponsableAcademicoController::class, 'store']);
Route::get('/responsable-academico', [ResponsableAcademicoController::class, 'index']);
Route::get('/responsable-academico/check', [ResponsableAcademicoController::class, 'check']);

// Rutas protegidas (solo para usuarios logueados)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Si necesitas editar/eliminar, déjalas aquí
    Route::put('/responsable-academico/{id}', [ResponsableAcademicoController::class, 'update']);
    Route::delete('/responsable-academico/{id}', [ResponsableAcademicoController::class, 'destroy']);
});