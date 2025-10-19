<?php

namespace App\Services;

use App\Repositories\FaseRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Fase;

class FaseService
{
    protected $faseRepository;

    public function __construct(FaseRepository $faseRepository)
    {
        $this->faseRepository = $faseRepository;
    }

    public function listar(?string $busqueda = null, int $perPage = 10)
    {
        return $this->faseRepository->paginate($busqueda, $perPage);
    }

    public function crear(array $data): Fase
    {
        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:abierto,cerrado',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Si se abre una nueva fase, cerrar las anteriores
        if ($data['estado'] === 'abierto') {
            $this->faseRepository->cerrarFasesActivas();
        }

        return $this->faseRepository->create($validator->validated());
    }

    public function actualizar(int $id, array $data): Fase
    {
        $fase = $this->faseRepository->findById($id);
        if (!$fase) {
            throw new \Exception('Fase no encontrada.');
        }

        $validator = Validator::make($data, [
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'sometimes|required|in:abierta,cerrado',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Si se cambia a "abierto", cerrar las demás fases
        if (isset($data['estado']) && $data['estado'] === 'abierto') {
            $this->faseRepository->cerrarFasesActivas();
        }

        return $this->faseRepository->update($fase, $validator->validated());
    }

    public function eliminar(int $id): bool
    {
        $fase = $this->faseRepository->findById($id);
        if (!$fase) {
            throw new \Exception('Fase no encontrada.');
        }

        return $this->faseRepository->delete($fase);
    }
}
