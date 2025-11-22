<?php

namespace App\Repositories;

use App\Models\Fase;
use Illuminate\Support\Facades\Log;

class FaseRepository
{
    public function all()
    {
        return Fase::all();
    }
    public function getFases()
    {
        return Fase::select('id', 'nombre')->get();
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

    public function update($id, array $data)
    {
        Log::debug('Actualizar Fase', ['id' => $id, 'data' => $data]);
        $fase = $this->findById($id);
        $fase->update($data);
        return $fase;
    }

    public function delete(Fase $fase): bool
    {
        return $fase->delete();
    }

    public function findNombre(string $nombre): ?Fase
    {
        return Fase::where('nombre', $nombre)->first();
    }
    public function cerrarFasesActivas()
    {
        return Fase::where('estado', 'abierto')->update(['estado' => 'cerrado']);
    }
}


