<?php

namespace App\Services;

use App\Models\AreaNivel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListarCompetidoresService
{
    public function listarCompetidores(?int $areaId, ?int $nivelId, int $page, int $perPage): LengthAwarePaginator
    {
        $query = AreaNivel::with([
            'area',
            'nivel',
            'inscripcions.competidor.institucion',
            'inscripcions.competidor.grado',
            'inscripcions.competidor.tutor', // tutor legal del competidor
            'inscripcions.tutor' // tutor académico
        ]);



        if ($areaId) {
            $query->where('id_area', $areaId);
        }

        if ($nivelId) {
            $query->where('id_nivel', $nivelId);
        }

        $areaNiveles = $query->get();

        $competidores = $areaNiveles->flatMap(function ($an) {
            return $an->inscripcions->map(function ($inscripcion) use ($an) {
                $c = $inscripcion->competidor;
                return [
                    'nombre' => $c->nombres . ' ' . $c->apellidos,
                    'ci' => $c->ci,
                    'grado' => $c->grado?->nombre_grado ?? '-',
                    'unidad_educativa' => $c->institucion?->nombre_institucion ?? '-',
                    'departamento' => $c->institucion?->departamento_institucion ?? '-',
                    'area' => $an->area->nombre_area,
                    'nivel' => $an->nivel->nombre_nivel,
                    'contacto_tutor_legal' => $c->tutor?->telefono ?? '-',
                    'contacto_tutor_academico' => $inscripcion->tutor?->telefono ?? '-',

                ];
            });
        })->values();

        // Paginación manual
        $total = $competidores->count();
        $items = $competidores->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
