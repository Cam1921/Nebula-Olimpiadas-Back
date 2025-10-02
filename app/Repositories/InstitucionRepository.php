<?php

namespace App\Repositories;

use App\Models\Institucion;

class InstitucionRepository
{
    public function firstOrCreateInstitucion($nombre, $departamento, $municipio = '')
    {
        return Institucion::firstOrCreate(
            ['nombre_institucion' => $nombre, 'departamento_institucion' => $departamento],
            ['municipio_institucion' => $municipio]
        );
    }
}
