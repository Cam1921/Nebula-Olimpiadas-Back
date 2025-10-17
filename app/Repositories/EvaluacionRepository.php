<?php

namespace App\Repositories;

use App\Models\Evaluacion;
use App\Traits\NormalizeStringTrait;
use DB;

class EvaluacionRepository
{

    /**
     * Obtiene todas las evaluaciones asignadas a un evaluador.
     *
     * @param int $idEvaluador
     * @return \Illuminate\Support\Collection
     */


    public function obtenerEvaluacionesPorEvaluador($idEvaluador)
    {
        /* return DB::table('evaluacion as eva')
            ->join('inscripcion as ins', 'ins.id', '=', 'eva.id_inscripcion')
            ->join('competidor as co', 'co.id', '=', 'ins.id_competidor')
            ->join('area_nivel as an', 'an.id', '=', 'ins.id_area_nivel')
            ->join('area as ar', 'ar.id', '=', 'an.id_area')
            ->join('nivel as nv', 'nv.id', '=', 'an.id_nivel')
            ->join('asignacion as asig', 'asig.id_area_nivel', '=', 'an.id')
            ->join('persona as pe', 'pe.id', '=', 'asig.id_persona')
            ->join('persona_rol as rp', 'rp.id_persona', '=', 'pe.id')
            ->join('rol as r', 'r.id', '=', 'rp.id_rol')
            ->where('pe.id', $idEvaluador)
            ->where('r.nombre', 'evaluador')
            ->select(
                'ins.id as id_inscrito',
                'eva.id as id_evaluacion',
                'co.ci as CI',
                'co.nombres as Nombre',
                'ar.nombre_area as Area',
                'nv.nombre_nivel as Nivel',
                'eva.nota as Nota',
                'eva.respeto as Respeto',
                'eva.integridad as Integridad',
                'eva.puntualidad as Puntualidad',
                'eva.descripcion as Descripcion',
                'eva.estado as Estado'
            )
            ->get(); */
        $evaluaciones = Evaluacion::with([
            'inscripcion.competidor',
            'inscripcion.area_nivel.area',
            'inscripcion.area_nivel.nivel',
            'inscripcion.area_nivel.asignacions.persona.rols'
        ])
            ->whereHas('inscripcion.area_nivel.asignacions.persona.rols', function ($query) {
                $query->where('nombre', 'evaluador');
            })
            ->whereHas('inscripcion.area_nivel.asignacions', function ($query) use ($idEvaluador) {
                $query->where('id_persona', $idEvaluador);
            })
            ->get();

        $evaluaciones = Evaluacion::with([
            'inscripcion.competidor',
            'inscripcion.area_nivel.area',
            'inscripcion.area_nivel.nivel'
        ])
            ->whereHas('inscripcion.area_nivel.asignacions.persona.rols', function ($query) {
                $query->where('nombre', 'evaluador');
            })
            ->whereHas('inscripcion.area_nivel.asignacions', function ($query) use ($idEvaluador) {
                $query->where('id_persona', $idEvaluador);
            })
            ->get()
            ->map(function ($eva) {
                return [
                    'id_inscrito' => $eva->inscripcion->id,
                    'id_evaluacion' => $eva->id,
                    'CI' => $eva->inscripcion->competidor->ci,
                    'Nombre' => $eva->inscripcion->competidor->nombres,
                    'Area' => $eva->inscripcion->area_nivel->area->nombre_area,
                    'Nivel' => $eva->inscripcion->area_nivel->nivel->nombre_nivel,
                    'Nota' => $eva->nota,
                    'Respeto' => $eva->respeto,
                    'Integridad' => $eva->integridad,
                    'Puntualidad' => $eva->puntualidad,
                    'Descripcion' => $eva->descripcion,
                    'Estado' => $eva->estado,
                ];
            });

        return $evaluaciones;
    }
}