<?php

namespace App\Repositories;

use App\Models\Grado;

class GradoRepository
{
    public function firstOrCreateGrado($nombre)
    {
        return Grado::firstOrCreate(
            ['nombre_grado' => $nombre],
            ['nombre_grado' => $nombre]
        );
    }
}
