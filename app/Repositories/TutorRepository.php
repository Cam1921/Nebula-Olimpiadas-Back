<?php

namespace App\Repositories;

use App\Models\Tutor;

class TutorRepository
{
    public function firstOrCreatePersona(array $data)
    {
        return Tutor::firstOrCreate(
            [
                'nombres' => $data['nombres'],
                'telefono' => $data['telefono']
            ],
            $data
        );
    }

    public function getDefaultTutorAcademico()
    {
        // 🔑 Aquí puedes configurar un "tutor académico" por defecto
        // Por ejemplo: la primera persona en la BD, o un registro fijo
        return Tutor::firstOrCreate(
            ['ci' => 'TUTOR-DEFAULT'],
            [
                'nombres' => 'Tutor',
                'apellidos' => 'Académico',
                'telefono' => '00000000',
                'email' => 'tutor@academico.com'
            ]
        );
    }
}
