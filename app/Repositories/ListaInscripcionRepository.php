<?php

namespace App\Repositories;

use App\Models\ListaInscripcion;

class ListaInscripcionRepository
{
    public function firstOrCreateLista($idOlimpiada)
    {
        return ListaInscripcion::firstOrCreate(
            ['id_olimpiada' => $idOlimpiada],
            ['id_olimpiada' => $idOlimpiada]
        );
    }
}
