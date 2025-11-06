<?php

namespace App\Repositories;

use App\Models\PersonaArea;

class AsignacionAreaRepository
{
    protected $model;
    public function __construct(PersonaArea $model)
    {
        $this->model = $model;
    }
    public function AsignacionAreaAll($rol)
    {
        return $this->model
            ->whereHas('persona.rols', function ($q) use ($rol) {
                $q->where('nombre', $rol);
            })
            ->get();
    }


}
