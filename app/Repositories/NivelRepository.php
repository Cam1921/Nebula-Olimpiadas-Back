<?php

namespace App\Repositories;

use App\Models\Nivel;

class NivelRepository
{
    public function firstOrCreateNivel($nombre)
    {
        return Nivel::firstOrCreate(['nombre_nivel' => $nombre], ['descripcion_nivel' => 'Sin descripción']);
    }
    public function getAllNiveles()
    {
        return Nivel::select('id', 'nombre_nivel')->get();
    }
}
