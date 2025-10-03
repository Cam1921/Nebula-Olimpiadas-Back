<?php

namespace App\Repositories;

use App\Models\AreaNivel;
use App\Traits\NormalizeStringTrait;

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
}
