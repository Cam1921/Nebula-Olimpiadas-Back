<?php

namespace App\Services;

use App\Models\AreaNivel;
use App\Models\Asignacion;
use App\Models\Persona;
use Illuminate\Support\Facades\Log;

class AsignacionService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function asignarEvaluadores(array $evaluadores, int $idAreaNivel)
    {
        Log::debug('evaluadores', [$evaluadores]);

        $areaNivel = AreaNivel::find($idAreaNivel);
        if (!$areaNivel) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Area y nivel no encontrada'
                ]
            ];
        }

        $errores = [];
        $filaErrores = [];

        foreach ($evaluadores as $evaluador) {
            $persona = Persona::find($evaluador);

            if (!$persona) {

                $filaErrores[] = [
                    'id_evaluador' => $evaluador,
                    'error' => "No se encuentra un evaluador con el id '{$evaluador}'"
                ];
                continue;
            }


            Asignacion::create([
                'id_persona' => $evaluador,
                'id_area_nivel' => $idAreaNivel
            ]);
        }


        $errores = array_merge($errores, $filaErrores);

        return [
            'status_code' => 201,
            'content' => [
                'status' => 'success',
                'message' => 'Asignaciones creadas correctamente',
                'errors' => $errores
            ]
        ];
    }
    public function eliminarEvaluadores(array $evaluadores, int $idAreaNivel)
    {
        Log::debug('evaluadores_a_eliminar', [$evaluadores]);

        // Verificar que existe el área/nivel
        $areaNivel = AreaNivel::find($idAreaNivel);
        if (!$areaNivel) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Area y nivel no encontrada'
                ]
            ];
        }

        $errores = [];
        $filaErrores = [];

        foreach ($evaluadores as $evaluador) {

            // 1. Verificar si existe la persona/evaluador
            $persona = Persona::find($evaluador);
            if (!$persona) {
                $filaErrores[] = [
                    'id_evaluador' => $evaluador,
                    'error' => "No se encuentra un evaluador con el id '{$evaluador}'"
                ];
                continue;
            }

            // 2. Buscar asignación para ese evaluador EN ESE área/nivel
            $asignacion = Asignacion::where('id_persona', $evaluador)
                ->where('id_area_nivel', $idAreaNivel)
                ->first();

            if (!$asignacion) {
                $filaErrores[] = [
                    'id_evaluador' => $evaluador,
                    'error' => "El evaluador '{$evaluador}' no tiene una asignación en esta área/nivel"
                ];
                continue;
            }

            // 3. Eliminar asignación
            $asignacion->delete();
        }

        // Combinar errores
        $errores = array_merge($errores, $filaErrores);

        return [
            'status_code' => 200,
            'content' => [
                'status' => 'success',
                'message' => 'Asignaciones eliminadas correctamente',
                'errors' => $errores
            ]
        ];
    }


}
