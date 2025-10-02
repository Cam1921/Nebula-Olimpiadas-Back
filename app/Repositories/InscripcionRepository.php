<?php

namespace App\Repositories;

use App\Models\Inscripcion;

class InscripcionRepository
{
    public function createInscripcion($idCompetidor, $idAreaNivel, $gestion)
    {
        return Inscripcion::create([
            'id_competidor' => $idCompetidor,
            'id_area_nivel' => $idAreaNivel,
            'gestion' => $gestion,
        ]);
    }
}
