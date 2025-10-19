<?php

namespace App\Repositories;

use App\Models\Evaluacion;
use App\Traits\NormalizeStringTrait;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;

class EvaluacionRepository
{
    use NormalizeStringTrait;

    public function obtenerEvaluacionesPorEvaluador(
        int $idEvaluador,
        ?string $busqueda = null,
        int $perPage = 10,
        int $page = 1,
        ?string $estado_clasificado = null
    ): LengthAwarePaginator {

        $query = Evaluacion::with([
            'inscripcion.competidor',
            'inscripcion.area_nivel.area',
            'inscripcion.area_nivel.nivel'
        ])

            ->whereHas('inscripcion.area_nivel.asignacions.persona.rols', function ($q) {
                $q->where('nombre', 'evaluador');
            })

            ->whereHas('inscripcion.area_nivel.asignacions', function ($q) use ($idEvaluador) {
                $q->where('id_persona', $idEvaluador);
            });


        if ($busqueda) {
            $query->whereHas('inscripcion.competidor', function ($q) use ($busqueda) {
                $q->where('nombres', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('apellidos', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('ci', 'ILIKE', "%{$busqueda}%");
            })
                ->orWhereHas('inscripcion.area_nivel.area', function ($q) use ($busqueda) {
                    $q->where('nombre_area', 'ILIKE', "%{$busqueda}%");
                });
        }
        if ($estado_clasificado) {
            switch ($estado_clasificado) {
                case 'clasificados':
                    $query->whereNotNull('nota') // solo evaluados
                        ->where('nota', '>=', 51)
                        ->where('respeto', true)
                        ->where('integridad', true)
                        ->where('puntualidad', true);
                    break;

                case 'no_clasificados':
                    $query->whereNotNull('nota')
                        ->where('nota', '<', 51)
                        ->where('respeto', true)
                        ->where('integridad', true)
                        ->where('puntualidad', true);
                    break;

                case 'descalificados':
                    $query->whereNotNull('nota')
                        ->where(function ($q) {
                            $q->where('respeto', false)
                                ->orWhere('integridad', false)
                                ->orWhere('puntualidad', false);
                        });
                    break;

                default:
                    break;
            }
        }

        $query->orderBy('id', 'asc');


        return $query->paginate($perPage, ['*'], 'page', $page);


    }


    public function update(Evaluacion $evaluacion, array $data)
    {
        return tap($evaluacion)->update($data);
    }
    public function findById($id)
    {
        return Evaluacion::findOrFail($id);
    }

}