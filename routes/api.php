<?php

use App\Http\Controllers\ActividadController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ControlFaseController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\EvaluacionesController;
use App\Http\Controllers\FaseController;
use App\Http\Controllers\ImportacionesController;
use App\Http\Controllers\ImportarEvaluadoresController;
use App\Http\Controllers\ListarInscritosCotroller;
use App\Http\Controllers\MedalleroController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PrepararEntornoFinalController;
use App\Http\Controllers\UserInviteController;
use App\Mail\SendTestEmail;
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

Route::prefix('catalogos')->group(function () {
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

Route::prefix('evaluador')->middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    Route::post('/', [EvaluadorController::class, 'store']);
    Route::get('/', [EvaluadorController::class, 'index']);
    Route::get('/check', [EvaluadorController::class, 'check']);
    Route::put('/{id}', [EvaluadorController::class, 'update']);
    Route::delete('/{id}', [EvaluadorController::class, 'destroy']);
    Route::post('/import/preview', [ImportarEvaluadoresController::class, 'preview']);
    Route::post('/import/confirmar', [ImportarEvaluadoresController::class, 'confirmar']);
    Route::get('/import/errores', [ImportarEvaluadoresController::class, 'descargarErrores']);
});


Route::prefix('fases')->group(function () {
    Route::get('/', [FaseController::class, 'index']);
    Route::get('/dropdown', [FaseController::class, 'obtenerFases']);
    Route::post('/publicar-todo', [FaseController::class, 'publicarTodo']);
    Route::get('/{id}', [FaseController::class, 'show']);
    Route::post('/', [FaseController::class, 'store']);
    Route::put('/{id}', [FaseController::class, 'update']);
    Route::delete('/{id}', [FaseController::class, 'destroy']);

    Route::get('/verificar/{nombreFase}', [FaseController::class, 'verificarFase']);
    Route::get('/{faseId}/actividades', [ActividadController::class, 'porFase']);

});

Route::prefix('actividades')->group(function () {
    Route::get('/', [ActividadController::class, 'index']);
    Route::get('/{id}', [ActividadController::class, 'show']);
    Route::post('/', [ActividadController::class, 'store']);
    Route::put('/{id}', [ActividadController::class, 'update']);
    Route::delete('/{id}', [ActividadController::class, 'destroy']);
    Route::get('/verificar/{nombreFase}/{nombreActividad}', [ActividadController::class, 'verificarActividad']);
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

Route::prefix('evaluador')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/evaluaciones/exportar', [EvaluacionesController::class, 'exportarExcel']);
    Route::get('/evaluaciones/{idAreaNivelFase}', [EvaluacionesController::class, 'index']);
    Route::put('/evaluaciones/{id}', [EvaluacionesController::class, 'update']);

    Route::get('/niveles', [EvaluacionesController::class, 'getEstadosAllFases']);
    Route::get('/niveles/{idFase}', [EvaluacionesController::class, 'getEstadosPorFase']);
    Route::post('/evaluaciones/otorgar-aval/{idAreaNivelFase}', [EvaluacionesController::class, 'otorgarAval']);

});

/* Route::get('send-mail', function () {
    $message = 'hello word';
    Mail::to('202108055@est.umss.edu')->send(new SendTestEmail($message));

}); */
Route::middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    Route::post('/invitaciones/send-mail/{id}', [UserInviteController::class, 'sendEmail']);
    Route::get('/notificaciones/listar', [UserInviteController::class, 'listarNotificaciones']);
    Route::put('/invitaciones/reenviar/{id}', [UserInviteController::class, 'resendEmail']);
});
Route::get('/invitaciones/verificar-token/{token}', [UserInviteController::class, 'verificarToken']);
Route::post('/invitaciones/establecer-password', [UserInviteController::class, 'establecerPassword']);
Route::middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    Route::get('/estados', [EstadoController::class, 'index']);
    Route::put('/estados/{id}', [EstadoController::class, 'actualizarEstado']);
    Route::get('/areas-fases', [EstadoController::class, 'getEstadosAreas']);
});

Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API funcionando correctamente',
    ]);
});

Route::get('/evaluaciones/exportar', [EvaluacionesController::class, 'exportarEvaluaciones']);
Route::get('/evaluaciones/filtrar', [EvaluacionesController::class, 'filtrar']);
Route::get('/entorno-final/niveles', [PrepararEntornoFinalController::class, 'index']);
Route::post('/entorno-final/preparar/{idAreaNivelFase}', [PrepararEntornoFinalController::class, 'prepararEntornoFinalPorAreaNivelFase']);

Route::get('/config-medallero', [MedalleroController::class, 'index']);
Route::post('/config-medallero/save-all', [MedalleroController::class, 'saveAll']);


//rutas para realizar la asignación

Route::prefix('asignaciones')->group(function () {
    Route::get('/', [AsignacionController::class, 'index']);
    Route::get('/{areaNivelId}/evaluadores', [AsignacionController::class, 'verEvaluadores']);
    Route::post('/{areaNivelId}/evaluadores', [AsignacionController::class, 'asignarEvaluadores']);
    Route::delete('/{areaNivelId}/evaluadores', [AsignacionController::class, 'quitarEvaluadores']);
    Route::get('/evaluadores/disponibles/{areaId}', [AsignacionController::class, 'evaluadoresDisponibles']);


    Route::get('/evaluadores/disponibles/{areaId}', [AsignacionController::class, 'evaluadoresDisponibles']);
    Route::post('/asignar-evaluadores', [AsignacionController::class, 'asignarInscritos']);
    Route::get('/evaluadores', [AsignacionController::class, 'listar']);

});
