<?php

namespace App\Repositories;

use App\Models\Institucion;

class InstitucionRepository
{
    public function firstOrCreateInstitucion(array $data)
    {
        return Institucion::firstOrCreate(
            [
                'nombre_institucion' => $data['nombre_institucion'],
                'departamento_institucion' => $data['departamento_institucion']
            ],
            $data
        );
    }
}
