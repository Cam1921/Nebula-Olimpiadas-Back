<?php

namespace App\Repositories;

use App\Models\Persona;
use App\Traits\NormalizeStringTrait;

class PersonaRepository
{

    use NormalizeStringTrait;
    /**
     * Crear o devolver persona existente según correo, CI o teléfono
     */
    public function firstOrCreatePersona(array $data)
    {
        $correo = strtolower(trim($data['email'] ?? ''));
        $ci = $this->normalizeString($data['ci'] ?? '');
        $telefono = $this->normalizeString($data['telefono'] ?? '');

        $persona = Persona::where(function ($q) use ($correo, $ci, $telefono) {
            $q->where('email', $correo)
                ->orWhere('ci', $ci)
                ->orWhere('telefono', $telefono);
        })->first();

        if (!$persona) {
            $persona = Persona::create([
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'email' => $correo,
                'ci' => $ci,
                'telefono' => $telefono,
            ]);
        }

        return $persona;
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
    public function getByCi($ci)
    {
        $ci = $this->normalizeString($ci);
        return Persona::with([
            'asignacions.area_nivel.area:id,nombre_area',
            'asignacions.area_nivel.nivel:id,nombre_nivel'
        ])->where('ci', $ci)
            ->first();
    }

    /**
     * Obtener persona por teléfono
     */
    public function getByTelefono($telefono)
    {
        $telefono = $this->normalizeString($telefono);
        return Persona::with([
            'asignacions.area_nivel.area:id,nombre_area',
            'asignacions.area_nivel.nivel:id,nombre_nivel'
        ])->where('telefono', $telefono)
            ->first();
    }

    /**
     * Obtener todas las personas de un rol, normalizadas (para validación CSV)
     */
    public function getAllNormalized($rolNombre)
    {
        $personas = Persona::whereHas('rols', function ($query) use ($rolNombre) {
            $query->where('nombre', $rolNombre);
        })->get();

        return $personas;
    }

    /**
     * Verificar si una persona ya existe (por correo, CI o teléfono)
     */
    public function exists(array $data)
    {
        $correo = strtolower(trim($data['email'] ?? ''));
        $ci = $this->normalizeString($data['ci'] ?? '');
        $telefono = $this->normalizeString($data['telefono'] ?? '');

        return Persona::where(function ($q) use ($correo, $ci, $telefono) {
            $q->where('email', $correo)
                ->orWhere('ci', $ci)
                ->orWhere('telefono', $telefono);
        })->exists();
    }
}
