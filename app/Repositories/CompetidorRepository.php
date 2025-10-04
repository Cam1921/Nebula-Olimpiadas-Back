<?php

namespace App\Repositories;

use App\Models\Competidor;

class CompetidorRepository
{
    public function createCompetidor($data)
    {
        return Competidor::create($data);
    }
    public function getCompetidores($idArea = null, $idNivel = null, $perPage = 10)
    {
        $query = Competidor::query()
            ->join('inscripcions', 'competidors.id', '=', 'inscripcions.id_competidor')
            ->join('area_nivels', 'inscripcions.id_area_nivel', '=', 'area_nivels.id')
            ->join('areas', 'area_nivels.id_area', '=', 'areas.id')
            ->join('nivels', 'area_nivels.id_nivel', '=', 'nivels.id')
            ->join('institucions', 'competidors.id_institucion', '=', 'institucions.id')
            ->join('tutor_competidors', 'competidors.id_tutor', '=', 'tutor_competidors.id')
            ->select(
                'competidors.id',
                'competidors.nombre_completo',
                'competidors.ci',
                'competidors.grado',
                'institucions.nombre_institucion',
                'areas.nombre_area',
                'nivels.nombre_nivel',
                'tutor_competidors.nombre_completo as nombre_tutor'
            );

        if ($idArea) {
            $query->where('areas.id', $idArea);
        }

        if ($idNivel) {
            $query->where('nivels.id', $idNivel);
        }

        return $query->paginate($perPage);
    }
}
