<?php

namespace App\Repositories;

use App\Models\Inscripcion;

class InscripcionRepository
{
    public function createInscripcion(array $data)
    {
        return Inscripcion::create([
            'id_competidor' => $data['id_competidor'],
            'id_area_nivel' => $data['id_area_nivel'],
            'id_lista_inscripcion' => $data['id_lista_inscripcion'],
            'id_tutor_academico' => $data['id_tutor_academico'] ?? null, // aquí permitimos null
        ]);
    }
}

