<?php

namespace App\Repositories;

use App\Models\Nivel;
use App\Traits\NormalizeStringTrait;

class NivelRepository
{
    use NormalizeStringTrait;

    public function findByNombre(string $nombre): ?Nivel
    {
        $nombreNormalizado = $this->normalizeString($nombre);

        return Nivel::all()->first(function ($n) use ($nombreNormalizado) {
            return $this->normalizeString($n->nombre_nivel) === $nombreNormalizado;
        });
    }
    public function getAllNiveles()
    {
        return Nivel::select('id', 'nombre_nivel')->get();
    }
    public function getAll()
    {
        return Nivel::all();
    }
    public function firstOrCreate(array $data)
    {
        $nombre = $this->normalizeString($data['nombre_nivel']);

        return Nivel::whereRaw('LOWER(unaccent(nombre_nivel)) = ?', [$nombre])
            ->first() ?? Nivel::create([
                        'nombre_nivel' => trim($data['nombre_nivel']),
                    ]);
    }
    public function getAllNormalized(): array
    {
        $niveles = Nivel::all(); // Trae todos los niveles de la base de datos

        $normalizedNiveles = [];

        foreach ($niveles as $nivel) {
            $normalizedName = $this->normalizeString($nivel->nombre_nivel);
            $normalizedNiveles[$normalizedName] = $nivel;
        }

        return $normalizedNiveles;
    }
}
