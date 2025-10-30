<?php

namespace App\Http\Controllers;

use App\Models\Invitacion;
use App\Models\User;
use App\Services\PersonaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserInviteController extends Controller
{

    protected $personaService;
    public function __construct(PersonaService $personaService)
    {
        $this->personaService = $personaService;
    }
    public function sendEmail($id): JsonResponse
    {
        $response = $this->personaService->enviarCorreoCreacionPassword($id);
        return response()->json($response, $response['code']);
    }
    public function resendEmail($id): JsonResponse
    {
        $response = $this->personaService->reenviarCorreoInvitacion($id);
        return response()->json($response, $response['code']);
    }
    public function listarNotificaciones()
    {
        $response = $this->personaService->listarNotificaciones();
        return response()->json($response, $response['code']);
    }
    public function verificarToken($token)
    {
        $invitacion = Invitacion::where('token', $token)
            ->where('token_expira_en', '>', now())
            ->first();

        if (!$invitacion) {
            return response()->json(['ok' => false, 'message' => 'Token inválido o expirado'], 404);
        }

        return response()->json(['ok' => true]);
    }
    public function establecerPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:12',
        ]);

        $invitacion = Invitacion::where('token', $request->token)
            ->where('token_expira_en', '>', now())
            ->first();

        if (!$invitacion) {
            return response()->json(['message' => 'Token inválido o expirado'], 404);
        }

        // Crear usuario (ajusta según tu modelo)
        $user = User::updateOrCreate(
            ['email' => $invitacion->email],
            [
                'name' => $invitacion->nombres . ' ' . $invitacion->apellidos,
                'password' => Hash::make($request->password),
            ]
        );

        // Actualizar invitación
        $invitacion->update([
            'estado' => 'Confirmado',
            'token' => null, // invalidar el token
        ]);

        return response()->json(['message' => 'Contraseña establecida correctamente ✅']);
    }
}
