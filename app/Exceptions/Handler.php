<?php

namespace App\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Loguear tipo de excepción
        Log::info('Excepción capturada: ' . get_class($exception));
        Log::info('Ruta: ' . $request->method() . ' ' . $request->fullUrl());


        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'Recurso no encontrado'
            ], 404);
        }

        // Otros errores
        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => 'Error interno del servidor',
            'exception' => config('app.debug') ? $exception->getMessage() : 'Contacte al administrador'
        ], 500);
    }
}
