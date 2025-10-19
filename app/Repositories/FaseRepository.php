<?php

namespace App\Repositories;

use App\Models\Fase;

class FaseRepository
{
    public function all()
    {
        return Fase::orderBy('id', 'desc')->get();
    }

    public function paginate(?string $busqueda = null, int $perPage = 10)
    {
        $query = Fase::query();

        if ($busqueda) {
            $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                ->orWhere('descripcion', 'ILIKE', "%{$busqueda}%");
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Fase
    {
        return Fase::find($id);
    }

    public function create(array $data): Fase
    {
        return Fase::create($data);
    }

    public function update(Fase $fase, array $data): Fase
    {
        $fase->update($data);
        return $fase;
    }

    public function delete(Fase $fase): bool
    {
        return $fase->delete();
    }

    public function cerrarFasesActivas()
    {
        return Fase::where('estado', 'abierto')->update(['estado' => 'cerrado']);
    }
}


