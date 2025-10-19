<?php

namespace App\Services;

use App\Models\Evaluacion;
use App\Repositories\EvaluacionRepository;
use DB;
use Dom\Notation;


class EvaluacionesService
{


    protected $evaluacionRepository;
    public function __construct(EvaluacionRepository $evaluacionRepository)
    {
        $this->evaluacionRepository = $evaluacionRepository;
    }
    public function obtenerEvaluacionesPorEvaluador($idEvaluador, $busqueda, $perPage, $page, $estado_clasificado)
    {
        $evaluaciones = $this->evaluacionRepository->obtenerEvaluacionesPorEvaluador($idEvaluador, $busqueda, $perPage, $page, $estado_clasificado);

        $evaluaciones->getCollection()->transform(function ($eva) {
            $estado_clasificado = null;
            if ($eva->nota !== null) {
                if ($eva->nota >= 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                    $estado_clasificado = "Clasificado";
                } elseif ($eva->nota < 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                    $estado_clasificado = "No clasificado";
                } elseif (!$eva->respeto || !$eva->integridad || !$eva->puntualidad) {
                    $estado_clasificado = "Descalificado";
                }
            }



            return [
                'id_inscrito' => $eva->inscripcion->id,
                'id_evaluacion' => $eva->id,
                'ci' => $eva->inscripcion->competidor->ci,
                'nombre' => $eva->inscripcion->competidor->nombres,
                'area' => $eva->inscripcion->area_nivel->area->nombre_area,
                'nivel' => $eva->inscripcion->area_nivel->nivel->nombre_nivel,
                'nota' => $eva->nota,
                'conducta' => [
                    'respeto' => $eva->respeto,
                    'integridad' => $eva->integridad,
                    'puntualidad' => $eva->puntualidad,
                ],
                'descripcion' => $eva->descripcion,
                'estado_clasificado' => $estado_clasificado ?? null,
                'estado' => $eva->estado,
            ];
        });


        return $evaluaciones;
    }
    public function actualizarEvaluacion($id, array $datos, $evaluadorId, $ip)
    {
        $evaluacion = $this->evaluacionRepository->findById($id);


        $antes = $evaluacion->only(['nota', 'descripcion', 'respeto', 'integridad', 'puntualidad']);


        if (isset($datos['conducta'])) {
            $evaluacion->respeto = $datos['conducta']['respeto'] ?? $evaluacion->respeto;
            $evaluacion->integridad = $datos['conducta']['integridad'] ?? $evaluacion->integridad;
            $evaluacion->puntualidad = $datos['conducta']['puntualidad'] ?? $evaluacion->puntualidad;
        }

        $evaluacion->nota = $datos['nota'] ?? $evaluacion->nota;
        $evaluacion->descripcion = $datos['descripcion'] ?? $evaluacion->descripcion;
        $evaluacion->estado = 'evaluado';

        $this->evaluacionRepository->update($evaluacion, $evaluacion->toArray());


        DB::table('evaluacion_auditoria')->insert([
            'id_evaluacion' => $evaluacion->id,
            'evaluador_id' => $evaluadorId,
            'cambios' => json_encode([
                'antes' => $antes,
                'despues' => $evaluacion->only(['nota', 'descripcion', 'respeto', 'integridad', 'puntualidad'])
            ]),
            'ip' => $ip,
            'created_at' => now()
        ]);


        $estado_clasifidato = $evaluacion->nota >= 51 ? 'clasificado' : 'desclasificado';
        return [
            "id" => $evaluacion->id,
            "nota" => $evaluacion->nota,
            "descripcion" => $evaluacion->descripcion,
            "estado" => $evaluacion->estado,
            'conducta' => [
                'respeto' => $evaluacion->respeto,
                'integridad' => $evaluacion->integridad,
                'puntualidad' => $evaluacion->puntualidad,
            ],
            "estado_clasificado" => $estado_clasifidato,
            "id_inscripcion" => $evaluacion->id_inscripcion,
            "id_fase" => $evaluacion->id_fase,
            "created_at" => $evaluacion->created_at,
            "updated_at" => $evaluacion->updated_at
        ];
    }



}
