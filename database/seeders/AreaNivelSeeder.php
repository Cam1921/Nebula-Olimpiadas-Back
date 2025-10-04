<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use App\Models\Nivel;
use App\Models\AreaNivel;

class AreaNivelSeeder extends Seeder
{
    public function run(): void
    {
        AreaNivel::truncate();

        $areas = Area::all();
        $niveles = Nivel::all();

        foreach ($areas as $area) {
            foreach ($niveles as $nivel) {
                AreaNivel::create([
                    'id_area' => $area->id,
                    'id_nivel' => $nivel->id
                ]);
            }
        }
    }
}
