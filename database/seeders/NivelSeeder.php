<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nivel;

class NivelSeeder extends Seeder
{
    public function run(): void
    {
        Nivel::truncate();

        $niveles = [
            ['nombre_nivel' => 'Primaria', 'descripcion_nivel' => 'Estudiantes de nivel primario'],
            ['nombre_nivel' => 'Secundaria', 'descripcion_nivel' => 'Estudiantes de nivel secundario']
        ];

        foreach ($niveles as $n) {
            Nivel::create($n);
        }
    }
}
