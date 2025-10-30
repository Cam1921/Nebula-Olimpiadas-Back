<?php

namespace App\Services;

use App\Models\AreaNivel;

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
        // Recorremos todas las asignaciones del área nivel
        foreach ($areaNivel->asignacions as $asignacion) {
            $persona = $asignacion->persona;
            if ($persona && $persona->rols->contains('nombre', 'responsable')) {
                return $persona; // devolvemos la persona
            }
        }

        // Si no encontramos ninguna persona con rol 'responsable'
        return null;
    }
}
