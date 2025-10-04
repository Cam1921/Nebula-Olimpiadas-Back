<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TutorCompetidor;

class TutorCompetidorSeeder extends Seeder
{
    public function run(): void
    {
        TutorCompetidor::truncate();

        $tutores = [
            [
                'nombre_completo' => 'María López',
                'telefono' => '76543210',
                'email' => 'maria@example.com'
            ],
            [
                'nombre_completo' => 'Carlos Gutiérrez',
                'telefono' => '71234567',
                'email' => 'carlos@example.com'
            ]
        ];

        foreach ($tutores as $t) {
            TutorCompetidor::create($t);
        }
    }
}
