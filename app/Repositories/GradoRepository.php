<?php

namespace App\Repositories;

use App\Models\Grado;
use App\Traits\NormalizeStringTrait;
use Illuminate\Support\Facades\DB; // <-- IMPORTANTE

class GradoRepository
{
    use NormalizeStringTrait;

    public function findByNombre(string $nombre): ?Grado
    {
        $nombreNormalizado = $this->normalizeString($nombre);

        return Grado::all()->first(function ($g) use ($nombreNormalizado) {
            return $this->normalizeString($g->nombre_grado) === $nombreNormalizado;
        });
    }
    public function firstOrCreate(array $data)
    {
        $nombre = $this->normalizeString($data['nombre_grado']);

        return Grado::whereRaw('LOWER(unaccent(nombre_grado)) = ?', [$nombre])
            ->first() ?? Grado::create([
                        'nombre_grado' => trim($data['nombre_grado']),
                    ]);
    }

    public function isGradoEnNivel(int $id_grado, int $id_nivel): bool
    {
        return DB::table('nivel_grado')
            ->where('id_grado', $id_grado)
            ->where('id_nivel', $id_nivel)
            ->exists();
    }
    public function getAllGradoNivel(): array
    {
        // Trae todas las relaciones grado-nivel
        return DB::table('nivel_grado')
            ->select('id_grado', 'id_nivel')
            ->get()
            ->reduce(function ($carry, $item) {
                $carry[$item->id_grado . '-' . $item->id_nivel] = true;
                return $carry;
            }, []);
    }
    public function getAllNormalized(): array
    {
        $grados = Grado::all(); // Trae todos los grados de la base de datos

        $normalizedGrados = [];

        foreach ($grados as $grado) {
            $normalizedName = $this->normalizeString($grado->nombre_grado);
            $normalizedGrados[$normalizedName] = $grado;
        }

        return $normalizedGrados;
    }
    public function getAll()
    {
        return Grado::all();
    }
}
