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

    public function isGradoEnNivel(int $id_grado, int $id_nivel): bool
    {
        return DB::table('nivel_grado')
            ->where('id_grado', $id_grado)
            ->where('id_nivel', $id_nivel)
            ->exists();
    }
}
