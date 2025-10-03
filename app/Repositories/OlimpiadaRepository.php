<?php

namespace App\Repositories;

use App\Models\Olimpiada;

class OlimpiadaRepository
{
    public function getOlimpiadaActiva()
    {
        // Puedes definir cómo seleccionas la "olimpiada activa"
        // Ejemplo: la última creada
        return Olimpiada::orderByDesc('id')->first();
    }
}
