<?php

namespace App\Repositories;

use App\Models\TutorCompetidor;

class TutorCompetidorRepository
{
    public function firstOrCreateTutor($nombre, $telefono, $email = '')
    {
        return TutorCompetidor::firstOrCreate(
            ['nombre_completo' => $nombre, 'telefono' => $telefono],
            ['email' => $email]
        );
    }
}
