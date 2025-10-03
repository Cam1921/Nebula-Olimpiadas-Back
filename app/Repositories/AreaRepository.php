<?php

namespace App\Repositories;

use App\Models\Area;
use App\Traits\NormalizeStringTrait;

class AreaRepository
{
    use NormalizeStringTrait;

    public function findByNombre(string $nombre): ?Area
    {
        $nombreNormalizado = $this->normalizeString($nombre);

        return Area::all()->first(function ($a) use ($nombreNormalizado) {
            return $this->normalizeString($a->nombre_area) === $nombreNormalizado;
        });
    }
}
