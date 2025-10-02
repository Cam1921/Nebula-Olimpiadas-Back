<?php

namespace App\Repositories;

use App\Models\Area;

class AreaRepository
{
    public function firstOrCreateArea($nombre)
    {
        return Area::firstOrCreate(['nombre_area' => $nombre], ['descripcion_area' => 'Sin descripción']);
    }
    public function getAllAreas()
    {
        $areas = Area::select('id', 'nombre_area')->get();
        return $areas;
    }
}
