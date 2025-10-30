<?php

namespace App\Repositories;

use App\Models\Persona;

class PersonaRepository
{
    public function firstOrCreatePersona(array $data)
    {
        //
    }

    public function getById($id)
    {
        $personas = Persona::with([
            'asignacions.area_nivel.area' => function ($q) {
                $q->select('id', 'nombre_area');
            },
            'asignacions.area_nivel.nivel' => function ($q) {
                $q->select('id', 'nombre_nivel');
            }
        ])->find($id);
        return $personas;
    }
    public function getByCorreo($correo)
    {
        $persona = Persona::with([
            'asignacions.area_nivel.area' => function ($q) {
                $q->select('id', 'nombre_area');
            },
            'asignacions.area_nivel.nivel' => function ($q) {
                $q->select('id', 'nombre_nivel');
            }
        ])
            ->where('email', $correo)
            ->first(); // 👈 devuelve solo una persona (no una colección)

        return $persona;
    }
}
