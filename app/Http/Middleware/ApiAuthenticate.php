<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si hay token y usuario autenticado
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autenticado. Token inválido o expirado.'
            ], 401);
        }

        // Verificar si el usuario tiene una relación válida con persona
        if (!$user->relationLoaded('persona')) {
            $user->load('persona');
        }

        if (!$user->persona) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario sin datos de persona vinculados.'
            ], 403);
        }

        // Verificar si tiene roles
        $roles = $user->persona->rols ?? collect();
        if ($roles->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'El usuario no tiene roles asignados.'
            ], 403);
        }

        // Si todo está bien, continuar con la solicitud
        return $next($request);
    }
}
