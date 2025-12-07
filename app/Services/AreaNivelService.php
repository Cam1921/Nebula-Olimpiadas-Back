<?php

namespace App\Services;

use App\Models\AreaNivel;
use App\Models\Asignacion;
use App\Models\Persona;
use Illuminate\Support\Facades\Log;

class AreaNivelService
{
    public function getResumenEvaluaciones(AreaNivel $areaNivel)
    {
        $evaluaciones = $areaNivel->inscripcions->flatMap->evaluacions;

        $resumen = [
            'clasificados' => 0,
            'desclasificados' => 0,
            'no_clasificados' => 0,
        ];

        foreach ($evaluaciones as $eva) {
            if ($eva->nota !== null) {
                if ($eva->nota >= 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                    $resumen['clasificados']++;
                } elseif ($eva->nota < 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                    $resumen['no_clasificados']++;
                } elseif (!$eva->respeto || !$eva->integridad || !$eva->puntualidad) {
                    $resumen['desclasificados']++;
                }
            }
        }

        return $resumen;
    }

    public function getProgresoEvaluacion(AreaNivel $areaNivel)
    {
        $evaluaciones = $areaNivel->inscripcions->flatMap->evaluacions;
        $total = $evaluaciones->count();
        if ($total === 0)
            return ['total' => 0, 'pendientes' => 0, 'evaluados' => 0, 'progreso' => '0%'];

        $evaluados = $evaluaciones->where('estado', 'evaluado')->count();
        $pendientes = $evaluaciones->where('estado', 'pendientes')->count();
        $progreso = ($evaluados / $total) * 100;

        return [
            'total' => $total,
            'pendientes' => $pendientes,
            'evaluados' => $evaluados,
            'progreso' => round($progreso, 2) . '%',
        ];
    }
    public function getResponsableAreaNivel(AreaNivel $areaNivel)
    {
        foreach ($areaNivel->asignacions as $asignacion) {
            $persona = $asignacion->persona;
            if ($persona && $persona->rols->contains('nombre', 'responsable')) {
                return $persona;
            }
        }
        return null;
    }

    public function listaEvaluadores(array $params, int $idAreaNivel)
    {
        $estado = explode(',', $params['estado'] ?? '');

        if (in_array('asignados', $estado) && !in_array('no_asignados', $estado)) {
            return $this->getEvaluadoresAsignados($idAreaNivel);
        }

        if (in_array('no_asignados', $estado)) {
            return $this->getEvaluadoresNoAsignados($idAreaNivel);
        }
        return [
            'status_code' => 200,
            'content' => [
                'status' => 'success',
                'data' => [],
                'message' => 'Evaluadores obtenidos correctamente.'
            ]
        ];
    }
    public function getEvaluadoresAsignados(int $idAreaNivel)
    {

        $areaNivel = AreaNivel::with([
            'asignacions.persona.rols'
        ])->find($idAreaNivel);

        if (!$areaNivel) {
            return response()->json(['message' => 'AreaNivel no encontrado'], 404);
        }
        $data = $areaNivel->asignacions
            ->filter(function ($asig) {
                return $asig->persona->rols->contains('nombre', 'evaluador');
            })
            ->map(function ($asig) {
                return [
                    'id_asignacion' => $asig->id,
                    'id_persona' => $asig->persona->id,
                    'nombres' => $asig->persona->nombres,
                    'apellidos' => $asig->persona->apellidos,
                    'ci' => $asig->persona->ci,
                    'email' => $asig->persona->email,
                ];
            })
            ->values();
        return [
            'status_code' => 200,

            'content' => [
                'status' => 'success',
                'data' => $data,
                'message' => 'Evaluadores obtenidos correctamente.'
            ]
        ];
    }
    public function getEvaluadoresNoAsignados(int $idAreaNivel)
    {
        $area_nivel = AreaNivel::findOrFail($idAreaNivel);
        if (!$area_nivel) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'el area nivel no se encuetra en asignacion'
                ]
            ];
        }

        $area_id = $area_nivel->id_area;
        $evaluadores_area = Persona::with(
            'persona_areas'
        )
            ->whereHas('persona_areas', function ($q) use ($area_id) {
                $q->where('id_area', $area_id);
            });

        $evaluadores_no_asignados = $evaluadores_area->whereDoesntHave('asignacions', function ($q) use ($idAreaNivel) {
            $q->where('id_area_nivel', $idAreaNivel);
        })
            ->get();

        $data = $evaluadores_no_asignados->map(function ($asig) {

            return [

                'id_asignacion' => $asig->persona_areas->first()->id,
                'id_persona' => $asig->id,
                'nombres' => $asig->nombres,
                'apellidos' => $asig->apellidos,
                'ci' => $asig->ci,
                'email' => $asig->email,
            ];
        });


        return [
            'status_code' => 200,
            'content' => [
                'status' => 'success',
                'data' => $data,
                'message' => 'Evaluadores obtenidos correctamente.'
            ]
        ];


    }
}
