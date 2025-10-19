<?php

namespace App\Services;

use App\Models\AreaNivel;
use App\Models\Competidor;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListarCompetidoresService
{
    public function listarCompetidores(?int $areaId, ?int $nivelId, ?string $busqueda, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Competidor::query()
            ->select([
                'competidor.id',
                DB::raw("CONCAT(competidor.nombres, ' ', competidor.apellidos) as nombre"),
                'competidor.ci',
                'grado.nombre_grado as grado',
                'institucion.nombre_institucion as unidad_educativa',
                'institucion.departamento_institucion as departamento',
                'area.nombre_area as area',
                'nivel.nombre_nivel as nivel',
                'tutor_legal.telefono as contacto_tutor_legal',
                'tutor_academico.telefono as contacto_tutor_academico',
                'equipo.nombre_equipo as equipo',
            ])
            ->join('inscripcion', 'inscripcion.id_competidor', '=', 'competidor.id')
            ->join('area_nivel', 'area_nivel.id', '=', 'inscripcion.id_area_nivel')
            ->join('area', 'area.id', '=', 'area_nivel.id_area')
            ->join('nivel', 'nivel.id', '=', 'area_nivel.id_nivel')
            ->leftJoin('equipo', 'equipo.id', '=', 'competidor.id_equipo')
            ->leftJoin('institucion', 'institucion.id', '=', 'competidor.id_institucion')
            ->leftJoin('grado', 'grado.id', '=', 'competidor.id_grado')
            ->leftJoin('tutor as tutor_legal', 'tutor_legal.id', '=', 'competidor.id_tutor_legal')
            ->leftJoin('tutor as tutor_academico', 'tutor_academico.id', '=', 'inscripcion.id_tutor_academico');


        // Aplicar filtros si se proporcionan
        if ($areaId) {
            $query->where('area_nivel.id_area', $areaId);
        }

        if ($nivelId) {
            $query->where('area_nivel.id_nivel', $nivelId);
        }
        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where(DB::raw("CONCAT(competidor.nombres, ' ', competidor.apellidos)"), 'LIKE', "%{$busqueda}%")
                    ->orWhere('competidor.ci', 'LIKE', "%{$busqueda}%")
                    ->orWhere('institucion.nombre_institucion', 'LIKE', "%{$busqueda}%");
            });
        }

        // Paginación directamente en la base de datos
        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}

