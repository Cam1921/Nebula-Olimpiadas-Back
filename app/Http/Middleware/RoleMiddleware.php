<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Maneja la verificación de roles para un usuario autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Obtener el usuario usando el guard de Sanctum
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Recolectar los roles del usuario a través de la relación personas -> rols
        $userRoles = collect();

        if ($user->personas) {
            $user->personas->each(function ($persona) use ($userRoles) {
                if ($persona->rols) {
                    $userRoles->push(...$persona->rols->pluck('nombre')->toArray());
                }
            });
        }

        $userRoles = $userRoles->unique();

        // Verificar si al menos uno de los roles requeridos coincide
        foreach ($roles as $role) {
            if ($userRoles->contains($role)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'No autorizado'], 403);
    }
}
