<?php

namespace App\Services;

use App\Models\Evaluacion;
use App\Repositories\AreaNivelFaseRepository;
use App\Repositories\EvaluacionRepository;
use App\Traits\ApiResponseTrait;
use DB;
use Dom\Notation;


class EvaluacionesService
{
    use ApiResponseTrait;

    protected $evaluacionRepository;


    protected $areaNivelFaseRepo;
    protected $areaNivelService;

    public function __construct(AreaNivelFaseRepository $areaNivelFaseRepo, AreaNivelService $areaNivelService, EvaluacionRepository $evaluacionRepository)
    {
        $this->areaNivelFaseRepo = $areaNivelFaseRepo;
        $this->areaNivelService = $areaNivelService;
        $this->evaluacionRepository = $evaluacionRepository;
    }
    public function obtenerEvaluacionesPorEvaluador($idEvaluador, $idAreaNivelFase, $busqueda, $perPage, $page, $estado_clasificado)
    {
        $evaluaciones = $this->evaluacionRepository->obtenerEvaluacionesPorEvaluador($idEvaluador, $idAreaNivelFase, $busqueda, $perPage, $page, $estado_clasificado);

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
                'fase' => $eva->fase->nombre ?? 'Sin fase',
                'nota' => $eva->nota,
                'conducta' => [
                    'respeto' => $eva->respeto,
                    'integridad' => $eva->integridad,
                    'puntualidad' => $eva->puntualidad,
                ],
                'descripcion' => $eva->descripcion,
                'estado_clasificado' => $estado_clasificado ?? null,
                'estado' => $eva->estado,
                'estado_confirmado' => $eva->estado_confirmacion,
                'observacion' => $eva->observacion
            ];
        });


        return $evaluaciones;
    }


    public function actualizarEvaluacion($id, array $datos, $evaluadorId, $ip)
    {
        $evaluacion = $this->evaluacionRepository->findById($id);


        $antes = $evaluacion->only(['nota', 'descripcion', 'observacion', 'estado_confirmacion', 'respeto', 'integridad', 'puntualidad']);


        if (isset($datos['conducta'])) {
            $evaluacion->respeto = $datos['conducta']['respeto'] ?? $evaluacion->respeto;
            $evaluacion->integridad = $datos['conducta']['integridad'] ?? $evaluacion->integridad;
            $evaluacion->puntualidad = $datos['conducta']['puntualidad'] ?? $evaluacion->puntualidad;
        }

        $evaluacion->nota = $datos['nota'] ?? $evaluacion->nota;
        $evaluacion->descripcion = $datos['descripcion'] ?? $evaluacion->descripcion;
        $evaluacion->observacion = $datos['observacion'] ?? $evaluacion->observacion;
        $evaluacion->estado_confirmacion = $datos['estado_confirmacion'] ?? $evaluacion->estado_confirmacion;

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


        $estadoClasificado = null;
        if ($evaluacion->nota !== null) {
            if ($evaluacion->nota >= 51 && $evaluacion->respeto && $evaluacion->integridad && $evaluacion->puntualidad) {
                $estadoClasificado = "Clasificado";
            } elseif ($evaluacion->nota < 51 && $evaluacion->respeto && $evaluacion->integridad && $evaluacion->puntualidad) {
                $estadoClasificado = "No clasificado";
            } elseif (!$evaluacion->respeto || !$evaluacion->integridad || !$evaluacion->puntualidad) {
                $estadoClasificado = "Descalificado";
            }
        }
        return [
            "id" => $evaluacion->id,
            "nota" => $evaluacion->nota,
            "descripcion" => $evaluacion->descripcion,
            "estado" => $evaluacion->estado,
            "observacion" => $evaluacion->observacion,
            "estado_confirmacion" => $evaluacion->estado_confirmacion,
            'conducta' => [
                'respeto' => $evaluacion->respeto,
                'integridad' => $evaluacion->integridad,
                'puntualidad' => $evaluacion->puntualidad,
            ],
            "estado_clasificado" => $estadoClasificado,
            "id_inscripcion" => $evaluacion->id_inscripcion,
            "id_fase" => $evaluacion->id_fase,
            "created_at" => $evaluacion->created_at,
            "updated_at" => $evaluacion->updated_at
        ];
    }
    public function getEstadosByEvaluador(int $evaluadorId, $fase = null)
    {
        // Cargar todas las relaciones necesarias (evita N+1)
        $query = $this->areaNivelFaseRepo->getAllWithEvaluacionesFases();

        // Filtramos por fase si se especifica
        if ($fase !== null) {
            if (is_numeric($fase)) {
                // Filtro por ID de fase
                $query = $query->where('id_fase', $fase);
            } else {
                // Filtro por nombre de fase
                $query = $query->filter(function ($anf) use ($fase) {
                    return strtolower($anf->fase?->nombre) === strtolower($fase);
                });
            }
        }

        // Filtrar por asignación del evaluador
        $areaNivelFases = $query->filter(function ($anf) use ($evaluadorId) {
            return $anf->area_nivel
                && $anf->area_nivel->asignacions
                && $anf->area_nivel->asignacions->contains('id_persona', $evaluadorId);
        });

        $resultado = [];

        foreach ($areaNivelFases as $anf) {
            $areaNivel = $anf->area_nivel;

            if (!$areaNivel)
                continue;

            $resumen = $this->areaNivelService->getResumenEvaluaciones($areaNivel);
            $progreso = $this->areaNivelService->getProgresoEvaluacion($areaNivel);
            $responsable = $this->areaNivelService->getResponsableAreaNivel($areaNivel);

            $resultado[] = [
                'id_area_nivel_fase' => $anf->id,
                'fase' => $anf->fase?->nombre ?? 'Sin fase',
                'area' => $areaNivel->area?->nombre_area ?? 'Sin área',
                'nivel' => $areaNivel->nivel?->nombre_nivel ?? 'Sin nivel',
                'responsable' => $responsable
                    ? $responsable->nombres . ' ' . $responsable->apellidos
                    : 'Sin responsable',
                'estado' => $anf->estado, // ✅ estado real de area_nivel_fase
                'resumen_evaluaciones' => $resumen,
                'progreso_evaluacion' => $progreso,
            ];
        }

        return $this->successResponse('Estados obtenidos correctamente', $resultado);
    }
    public function filtrarEvaluaciones(array $filtros)
    {
        $evaluaciones = $this->evaluacionRepository->obtenerEvaluacionesFiltradas(
            idFase: $filtros['id_fase'] ?? null,
            idArea: $filtros['id_area'] ?? null,
            idNivel: $filtros['id_nivel'] ?? null,
            nivelNombre: $filtros['nivel_nombre'] ?? null,
            busqueda: $filtros['busqueda'] ?? null,
            perPage: $filtros['per_page'] ?? 10,
            page: $filtros['page'] ?? 1,
            estado_clasificado: $filtros['estado_clasificado'] ?? null
        );

        // Agregar campo calculado "estado_clasificado"
        $evaluaciones->getCollection()->transform(function ($eva) {
            if ($eva->nota >= 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                $estado = "Clasificado";
            } elseif ($eva->nota < 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                $estado = "No clasificado";
            } elseif (!$eva->respeto || !$eva->integridad || !$eva->puntualidad) {
                $estado = "Descalificado";
            } else {
                $estado = "Sin determinar";
            }

            $eva->estado_clasificado = $estado;
            return [
                'id_inscrito' => $eva->inscripcion->id,
                'id_evaluacion' => $eva->id,
                'ci' => $eva->inscripcion->competidor->ci,
                'nombre' => $eva->inscripcion->competidor->nombres . ' ' . $eva->inscripcion->competidor->apellidos,
                'grado ' => $eva->inscripcion->competidor->grado->nombre_grado,
                'area' => $eva->inscripcion->area_nivel->area->nombre_area,
                'nivel' => $eva->inscripcion->area_nivel->nivel->nombre_nivel,
                'fase' => $eva->fase->nombre ?? 'Sin fase',
                'nota' => $eva->nota,
                'conducta' => [
                    'respeto' => $eva->respeto,
                    'integridad' => $eva->integridad,
                    'puntualidad' => $eva->puntualidad,
                ],
                'descripcion' => $eva->descripcion,
                'estado_clasificado' => $eva->estado_clasificado ?? null,
                'estado' => $eva->estado,
                'estado_confirmado' => $eva->estado_confirmacion,
                'observacion' => $eva->observacion
            ];
        });

        return $evaluaciones;
    }

}
