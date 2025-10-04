<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competidor;
use App\Models\Institucion;
use App\Models\TutorCompetidor;

class CompetidorSeeder extends Seeder
{
    public function run(): void
    {
        Competidor::truncate();

        $instituciones = Institucion::all();
        $tutores = TutorCompetidor::all();

        $competidores = [
            [
                'nombre_completo' => 'Juan Pérez',
                'ci' => '1234567',
                'grado' => '6to',
                'id_institucion' => $instituciones->first()->id,
                'id_tutor' => $tutores->first()->id
            ],
            [
                'nombre_completo' => 'Ana Rodríguez',
                'ci' => '7654321',
                'grado' => '2do',
                'id_institucion' => $instituciones->last()->id,
                'id_tutor' => $tutores->last()->id
            ],
            [
                'nombre_completo' => 'Jhonny Pedro',
                'ci' => '12343257',
                'grado' => '5to',
                'id_institucion' => 2,
                'id_tutor' => 2
            ],
            [
                'nombre_completo' => 'Alexia Choque',
                'ci' => '7865321',
                'grado' => '2do',
                'id_institucion' => 2,
                'id_tutor' => 2
            ]
        ];

        foreach ($competidores as $c) {
            Competidor::create($c);
        }
    }
}
