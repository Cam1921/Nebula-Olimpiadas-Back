<?php

namespace App\Repositories;

use App\Models\Asignacion;

class AsignacionRepository
{
    protected $model;
    public function __construct(Asignacion $asignacion)
    {
        $this->model = $asignacion;
    }

    public function buscarPorAreaNivelRol($areaId, $nivelId, $rolId)
    {
        return $this->model
            ->whereHas('area_nivel', function ($query) use ($areaId, $nivelId) {
                $query->where('area_id', $areaId)
                    ->where('nivel_id', $nivelId);
            })
            ->whereHas('persona.rols', function ($query) use ($rolId) {
                $query->where('id', $rolId);
            })
            ->first();
    }

    public function getAllRolAreaNivelIds($rol)
    {
        return $this->model
            ->whereHas('persona.rols', function ($query) use ($rol) {
                $query->where('nombre', $rol);
            })
            ->pluck('id_area_nivel') // obtenemos solo los IDs de area_nivel
            ->toArray();             // convertimos a array
    }

}
