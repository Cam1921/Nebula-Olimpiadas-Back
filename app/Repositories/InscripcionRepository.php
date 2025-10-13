<?php

namespace App\Repositories;

use App\Models\Inscripcion;
use Illuminate\Support\Facades\DB;

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
    public function getAllByOlimpiada(int $olimpiadaId): array
    {
        // Hacemos join para obtener CI, area y nivel correctamente
        return DB::table('inscripcion as i')
            ->join('competidor as c', 'i.id_competidor', '=', 'c.id')
            ->join('area_nivel as an', 'i.id_area_nivel', '=', 'an.id')
            ->where('an.id_olimpiada', $olimpiadaId)
            ->select('c.ci', 'an.id_area', 'an.id_nivel')
            ->get()
            ->reduce(function ($carry, $item) {
                $carry[$item->ci . '-' . $item->id_area . '-' . $item->id_nivel] = true;
                return $carry;
            }, []);
    }

}

