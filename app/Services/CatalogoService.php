<?php

namespace App\Services;

use App\Models\Fase;
use App\Models\Rol;

class CatalogoService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getFases()
    {
        $fases = Fase::select('id', 'nombre')->get();
        return [
            'status_code' => 200,
            'content' => [
                'status' => 'success',
                'message' => 'Fases obtenidas',
                'data' => $fases
            ],

        ];
    }
    public function getRoles()
    {
        $roles = Rol::select('id', 'nombre')->get();
        return [
            'status_code' => 200,
            'content' => [
                'status' => 'success',
                'message' => 'Fases obtenidas',
                'data' => $roles
            ],

        ];
    }
}
