<?php

namespace App\Repositories;

use App\Models\Nivel;
use App\Traits\NormalizeStringTrait;

class NivelRepository
{
    use NormalizeStringTrait;

    public function findByNombre(string $nombre): ?Nivel
    {
        $nombreNormalizado = $this->normalizeString($nombre);

        return Nivel::all()->first(function ($n) use ($nombreNormalizado) {
            return $this->normalizeString($n->nombre_nivel) === $nombreNormalizado;
        });
    }
    public function getAllNiveles()
    {
        return Nivel::select('id', 'nombre_nivel')->get();
    }
}
