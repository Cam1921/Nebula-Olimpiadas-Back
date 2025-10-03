<?php

namespace App\Repositories;

use App\Models\AreaNivel;

class AreaNivelRepository
{
    public function firstOrCreateAreaNivel($idArea, $idNivel, $idOlimpiada)
    {
        return AreaNivel::firstOrCreate(
            [
                'id_area' => $idArea,
                'id_nivel' => $idNivel,
                'id_olimpiada' => $idOlimpiada,
            ]
        );
    }
}
