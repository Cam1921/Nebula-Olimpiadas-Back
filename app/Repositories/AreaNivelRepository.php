<?php

namespace App\Repositories;

use App\Models\AreaNivel;
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
}
