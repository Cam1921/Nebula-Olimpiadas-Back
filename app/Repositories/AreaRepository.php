<?php

namespace App\Repositories;

use App\Models\Area;
use App\Traits\NormalizeStringTrait;

class AreaRepository
{
    use NormalizeStringTrait;

    public function findByNombre(string $nombre): ?Area
    {
        $nombreNormalizado = $this->normalizeString($nombre);

        return Area::all()->first(function ($a) use ($nombreNormalizado) {
            return $this->normalizeString($a->nombre_area) === $nombreNormalizado;
        });
    }
    public function firstOrCreate(array $data)
    {
        $nombre = $this->normalizeString($data['nombre_area']);

        return Area::whereRaw('LOWER(unaccent(nombre_area)) = ?', [$nombre])
            ->first() ?? Area::create([
                        'nombre_area' => trim($data['nombre_area']),
                    ]);
    }

    public function getAll()
    {
        return Area::all();
    }
    public function getAllNormalized(): array
    {
        $areas = Area::all(); // Trae todas las áreas de la base de datos

        // Crear un array donde la clave es el nombre normalizado y el valor es el objeto Area
        $normalizedAreas = [];

        foreach ($areas as $area) {
            $normalizedName = $this->normalizeString($area->nombre_area);
            $normalizedAreas[$normalizedName] = $area;
        }

        return $normalizedAreas;
    }
}
