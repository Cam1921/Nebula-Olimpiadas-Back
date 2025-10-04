<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inscripcion;
use App\Models\Competidor;
use App\Models\AreaNivel;

class InscripcionSeeder extends Seeder
{
    public function run(): void
    {
        Inscripcion::truncate();

        $competidores = Competidor::all();
        $areaNiveles = AreaNivel::all();
        $gestion = now()->year;

        // Inscribir cada competidor en un área-nivel diferente para probar
        foreach ($competidores as $index => $competidor) {
            Inscripcion::create([
                'id_competidor' => $competidor->id,
                'id_area_nivel' => $areaNiveles[$index % $areaNiveles->count()]->id,
                'gestion' => $gestion
            ]);
        }
    }
}
