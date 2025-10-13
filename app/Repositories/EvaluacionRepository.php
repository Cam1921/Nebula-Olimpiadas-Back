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
        return DB::table('evaluacion as eva')
            ->join('inscripcion as ins', 'ins.id', '=', 'eva.id_inscripcion')
            ->join('competidor as co', 'co.id', '=', 'ins.id_competidor')
            ->join('area_nivel as an', 'an.id', '=', 'ins.id_area_nivel')
            ->join('area as ar', 'ar.id', '=', 'an.id_area')
            ->join('nivel as nv', 'nv.id', '=', 'an.id_nivel')
            ->where('eva.id_evaluador', $idEvaluador)
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
            ->get();
    }
}