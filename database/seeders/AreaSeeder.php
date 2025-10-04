<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        Area::truncate();

        $areas = [
            ['nombre_area' => 'Matemáticas', 'descripcion_area' => 'Área de lógica y razonamiento matemático'],
            ['nombre_area' => 'Lenguaje', 'descripcion_area' => 'Área de comprensión lectora y gramática'],
            ['nombre_area' => 'Ciencias', 'descripcion_area' => 'Área de física, química y biología']
        ];

        foreach ($areas as $a) {
            Area::create($a);
        }
    }
}
