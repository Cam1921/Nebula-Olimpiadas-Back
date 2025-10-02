<?php

namespace App\Repositories;

use App\Models\AreaNivel;

class AreaNivelRepository
{
    public function firstOrCreateAreaNivel($idArea, $idNivel)
    {
        return AreaNivel::firstOrCreate(['id_area' => $idArea, 'id_nivel' => $idNivel]);
    }
}
