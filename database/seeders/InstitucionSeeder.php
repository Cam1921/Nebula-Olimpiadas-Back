<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Institucion;

class InstitucionSeeder extends Seeder
{
    public function run(): void
    {
        Institucion::truncate();

        $instituciones = [
            [
                'nombre_institucion' => 'Colegio San Martín',
                'departamento_institucion' => 'Cochabamba',
                'municipio_institucion' => 'Cercado'
            ],
            [
                'nombre_institucion' => 'Colegio Bolívar',
                'departamento_institucion' => 'La Paz',
                'municipio_institucion' => 'Murillo'
            ]
        ];

        foreach ($instituciones as $i) {
            Institucion::create($i);
        }
    }
}
