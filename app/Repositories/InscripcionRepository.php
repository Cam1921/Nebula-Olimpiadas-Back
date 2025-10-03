<?php

namespace App\Repositories;

use App\Models\Inscripcion;

class InscripcionRepository
{
    public function existeInscripcion(string $ci, int $areaId, int $nivelId, int $olimpiadaId): bool
    {
        return Inscripcion::whereHas('competidor', function ($q) use ($ci) {
            $q->where('ci', $ci);
        })
            ->whereHas('area_nivel', function ($q) use ($areaId, $nivelId, $olimpiadaId) {
                $q->where('id_area', $areaId)
                    ->where('id_nivel', $nivelId)
                    ->where('id_olimpiada', $olimpiadaId);
            })
            ->exists();
    }
    public function createInscripcion(array $data)
    {
        return Inscripcion::create([
            'id_competidor' => $data['id_competidor'],
            'id_area_nivel' => $data['id_area_nivel'],
            'id_lista_inscripcion' => $data['id_lista_inscripcion'],
            'id_tutor_academico' => $data['id_tutor_academico'] ?? null, // aquí permitimos null
        ]);
    }

}

