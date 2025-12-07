<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Evaluacion;
use App\Models\Persona;
use App\Models\User;
use App\Notifications\ReclamoEvaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotificacionController extends Controller
{

    public function getNotificaciones()
    {
        $persona = Auth::user()->personas()->first();
        if (!$persona) {
            return response()->json([
                'status' => 'error',
                'message' => 'Persona no encontrada'
            ]);
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'todas' => $persona->notifications()->get(),
                'no_leidas' => $persona->unreadNotifications
            ]
        ]);
    }
    public function marcarComoLeida($id_notificacion)
    {
        $persona = Auth::user()->personas()->first();
        if (!$persona) {
            return response()->json([
                'status' => 'error',
                'message' => 'Persona no encontrada'
            ], 404);
        }

        $notificacion = $persona->notifications()->find($id_notificacion);

        if (!$notificacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notificación no encontrada'
            ]);
        }

        // ⚡ Aquí falta marcarla como leída
        $notificacion->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notificación marcada como leída',
        ]);
    }


    public function enviar(Request $request)
    {
        $request->validate([
            'id_evaluacion' => 'required|exists:evaluacion,id',
            'nombre_competidor' => 'required|string',
            'ci_competidor' => 'required|string',
            'area' => 'required|string',
            'nivel' => 'required|string',
            'motivo' => 'required|string'

        ]);

        $usuario = Auth::user();
        if (!$usuario) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Obtener la persona responsable
        $responsable = Persona::where('id_usuario', $usuario->id)->first();
        if (!$responsable) {
            return response()->json([
                'status' => 'error',
                'message' => 'Responsable no encontrado'
            ], 404);
        }

        // Obtener evaluación y asignación
        $evaluacion = Evaluacion::find($request->id_evaluacion);
        $asignacion = Asignacion::find($evaluacion->id_asignacion);
        $persona = $asignacion->persona;

        $data = [
            'responsable' => $responsable->nombres . ' ' . $responsable->apellidos,
            'nombre_competidor' => $request->nombre_competidor,
            'ci_competidor' => $request->ci_competidor,
            'area' => $request->area,
            'nivel' => $request->nivel,
            'motivo' => $request->motivo
        ];

        // Enviar notificación
        $persona->notify(new ReclamoEvaluacion($data));

        return response()->json([
            'status' => 'success',
            'message' => 'Notificación enviada correctamente'
        ]);
    }

}
