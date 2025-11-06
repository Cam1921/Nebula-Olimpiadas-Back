<?php

namespace App\Repositories;

use App\Models\AreaNivel;
use App\Models\AreaNivelFase;
use App\Traits\NormalizeStringTrait;
use Illuminate\Support\Facades\DB;

class AreaNivelRepository
{
    use NormalizeStringTrait;

    public function findAreaNivel(int $areaId, int $nivelId, int $olimpiadaId): ?AreaNivel
    {
        return AreaNivel::where('id_area', $areaId)
            ->where('id_nivel', $nivelId)
            ->where('id_olimpiada', $olimpiadaId)
            ->first();
    }
    public function getAllByOlimpiada(int $olimpiadaId): array
    {
        // Trae todas las relaciones área-nivel para la olimpiada
        return DB::table('area_nivel')
            ->where('id_olimpiada', $olimpiadaId)
            ->select('id_area', 'id_nivel')
            ->get()
            ->reduce(function ($carry, $item) {
                $carry[$item->id_area . '-' . $item->id_nivel] = true;
                return $carry;
            }, []);
    }

    public function getIdAllOlimpiada(int $olimpiadaId): array
    {
        return DB::table('area_nivel')
            ->where('id_olimpiada', $olimpiadaId)
            ->selectRaw("id, CONCAT(id_area, '-', id_nivel) as key")
            ->pluck('id', 'key')
            ->toArray();
    }
    public function getEvaluaciones(int $id_area_nivel, int $id_fase)
    {
        return AreaNivelFase::where('id_area_nivel', $id_area_nivel)
            ->where('id_fase', $id_fase)
            ->with([
                'fase',
                'area_nivel.area',
                'area_nivel.nivel',
                'area_nivel.inscripcions.evaluacions'
            ])
            ->first(); // devuelve un solo registro
    }




    /* $evaluaciones = $this->inscripcions->flatMap->evaluacions;

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

    return $resumen; */


    /*   $evaluaciones = $this->inscripcions->flatMap->evaluacions;
      $total = $evaluaciones->count();
      $evaluados = $evaluaciones->where('estado', 'evaluado')->count();
      $pendientes = $evaluaciones->where('estado', 'pendientes')->count();
      $progreso = ($evaluados / $total) * 100;

      return [
          'total' => $total,
          'pendientes' => $pendientes,
          'evaluados' => $evaluados,
          'progreso' => $progreso . '%',
      ];
*/


}
