<?php

namespace App\Repositories;

use App\Models\Equipo;
use App\Traits\NormalizeStringTrait;

class EquipoRepository
{
    use NormalizeStringTrait;
    protected $model;

    public function __construct(Equipo $equipo)
    {
        $this->model = $equipo;
    }
    public function firstOrCreate(array $data)
    {
        // Quita acentos y normaliza el nombre
        $nombreNormalizado = $this->normalizeString($data['nombre_equipo']);

        return Equipo::all()->first(function ($equipo) use ($nombreNormalizado) {
            return $this->normalizeString($equipo->nombre_equipo) === $nombreNormalizado;
        }) ?? Equipo::create([
                        'nombre_equipo' => trim($data['nombre_equipo']),
                    ]);
    }
    public function all()
    {
        return $this->model->all();
    }

    public function getAllNormalized(): array
    {
        $equipos = Equipo::all(); // Trae todos los equipos de la base de datos

        // Crear un array donde la clave es el nombre normalizado y el valor es el objeto Equipo
        $normalizedEquipos = [];

        foreach ($equipos as $equipo) {
            $normalizedName = $this->normalizeString($equipo->nombre_equipo);
            $normalizedEquipos[$normalizedName] = $equipo;
        }

        return $normalizedEquipos;
    }
    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $equipo = $this->model->find($id);
        if ($equipo) {
            $equipo->update($data);
            return $equipo;
        }
        return null;
    }

    public function delete($id)
    {
        $equipo = $this->model->find($id);
        if ($equipo) {
            return $equipo->delete();
        }
        return false;
    }
}
