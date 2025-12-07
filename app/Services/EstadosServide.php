<?php

namespace App\Services;

use App\Models\AreaNivelFase;
use App\Models\Evaluacion;
use App\Models\Fase;
use App\Models\Ranking;
use App\Repositories\AreaNivelFaseRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;

class EstadosServide
{
    use ApiResponseTrait;
    protected $areaNivelFaseRepo;
    protected $areaNivelService;

    public function __construct(AreaNivelFaseRepository $areaNivelFaseRepo, AreaNivelService $areaNivelService)
    {
        $this->areaNivelFaseRepo = $areaNivelFaseRepo;
        $this->areaNivelService = $areaNivelService;
    }

    public function getAllEstadoAreaNivel()
    {
        $areaNivelFases = $this->areaNivelFaseRepo->getAllWithEvaluaciones();
        $resultado = [];

        foreach ($areaNivelFases as $anf) {
            $areaNivel = $anf->area_nivel;

            if (!$areaNivel) {
                // Este registro no tiene area_nivel, saltamos o ponemos valores por defecto
                continue;
            }
            $resumen = $this->areaNivelService->getResumenEvaluaciones($areaNivel);
            $progreso = $this->areaNivelService->getProgresoEvaluacion($areaNivel);
            $responsable = $this->areaNivelService->getResponsableAreaNivel($areaNivel);
            $resultado[] = [
                'id_area_nivel_fase' => $anf->id,
                'fase' => $anf->fase->nombre,
                'area' => $areaNivel->area->nombre_area,
                'nivel' => $areaNivel->nivel->nombre_nivel,
                'responsable' => $responsable?->nombres . ' ' . $responsable?->apellidos,
                'estado' => $anf->estado,
                'resumen_evaluaciones' => $resumen,
                'progreso_evaluacion' => $progreso,
            ];
        }

        return $this->successResponse('estados obtenidos correctamente', [$resultado]);
    }
    public function getAllEstadoAreaNivelConfirmado()
    {
        $areaNivelFases = $this->areaNivelFaseRepo->getAllWithEvaluaciones();
        $resultado = [];

        foreach ($areaNivelFases as $anf) {
            $areaNivel = $anf->area_nivel;
            if ($anf->estado !== 'confirmado' && $anf->fase->nombre !== 'Clasificación') {
                continue;
            }
            if (!$areaNivel) {

                continue;
            }
            $resumen = $this->areaNivelService->getResumenEvaluaciones($areaNivel);
            $progreso = $this->areaNivelService->getProgresoEvaluacion($areaNivel);
            $responsable = $this->areaNivelService->getResponsableAreaNivel($areaNivel);
            $resultado[] = [
                'id_area_nivel_fase' => $anf->id,
                'fase' => $anf->fase->nombre,
                'area' => $areaNivel->area->nombre_area,
                'nivel' => $areaNivel->nivel->nombre_nivel,
                'responsable' => $responsable?->nombres . ' ' . $responsable?->apellidos,
                'estado' => $anf->estado,
                'resumen_evaluaciones' => $resumen,
                'progreso_evaluacion' => $progreso,
            ];
        }

        return $this->successResponse('estados obtenidos correctamente', [$resultado]);
    }
    public function migrarEvaluaciones()
    {
        DB::beginTransaction();

        try {
            $faseFinal = DB::table('fase')->where('nombre', 'Final')->first();
            $faseClasificatoria = DB::table('fase')->where('nombre', 'Clasificación')->first();
            $areaNiveles = DB::table('area_nivel')->get();
            if (!$faseFinal) {
                throw new \Exception("No existe la fase final 'Evaluación' en la tabla fase.");
            }
            foreach ($areaNiveles as $areaNivel) {
                $AreaNivelFase = AreaNivelFase::where('id_area_nivel', $areaNivel->id)->where('id_fase', $faseFinal->id)->first();
                if ($AreaNivelFase) {
                    continue;
                }
                DB::table('area_nivel_fase')->insertGetId([
                    'estado' => 'En_evaluacion',
                    'fecha_ini' => now(),
                    'fecha_fin' => now()->addDays(15),
                    'id_area_nivel' => $areaNivel->id, // cada areaNivel
                    'id_fase' => $faseFinal->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::insert("
            INSERT INTO evaluacion (nota, descripcion, estado, id_inscripcion, id_fase, created_at, updated_at)
            SELECT 
                NULL AS nota,
                NULL AS descripcion,
                'pendiente',
                e.id_inscripcion,
                :idFaseFinal,
                NOW(),
                NOW()
            FROM evaluacion e
            JOIN inscripcion i ON i.id = e.id_inscripcion
            WHERE i.id_area_nivel = :idAreaNivel
              AND e.id_fase = :idFaseClasificatoria
              AND e.nota >= 51
              AND e.respeto = TRUE
              AND e.integridad = TRUE
              AND e.puntualidad = TRUE
        ", [
                    'idFaseFinal' => $faseFinal->id,
                    'idAreaNivel' => $areaNivel->id,
                    'idFaseClasificatoria' => $faseClasificatoria->id,
                ]);

            }
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function migrarEvaluacionesRanking(
        $idAreaNivelFase = null
    ) {
        if (!$idAreaNivelFase) {
            return;
        }
        $faseActiva = Fase::where('estado', 'activa')->first();
        if (!$faseActiva) {
            return;
        }
        if (!in_array($faseActiva->nombre, ['clasificacion', 'final'])) {
            return;
        }
        $evaluaciones = Evaluacion::where('id_fase', $faseActiva->id)
            ->whereNotNull('nota')
            ->where('estado_confirmacion', 'aprobado') // ya confirmados y publicados
            ->orderByDesc('nota')
            ->whereHas('inscripcion.area_nivel.area_nivel_fase', function ($q) use ($idAreaNivelFase) {
                $q->where('id', $idAreaNivelFase);
            })->get();




        if ($evaluaciones->isEmpty()) {
            return;
        }

        // 3. Asignación de posiciones (considerando EMPATES)
        $posicion = 1;
        $posicionReal = 1;
        $notaAnterior = null;

        foreach ($evaluaciones as $evaluacion) {

            if ($notaAnterior !== null && $evaluacion->nota < $notaAnterior) {
                // Si la nota es menor, se incrementa la posición real
                $posicionReal++;
            }
            $estado_clasificado = null;
            if ($evaluacion->nota !== null) {
                if ($evaluacion->nota >= 51 && $evaluacion->respeto && $evaluacion->integridad && $evaluacion->puntualidad) {
                    $estado_clasificado = "Clasificado";
                } elseif ($evaluacion->nota < 51 && $evaluacion->respeto && $evaluacion->integridad && $evaluacion->puntualidad) {
                    $estado_clasificado = "No clasificado";
                } elseif (!$evaluacion->respeto || !$evaluacion->integridad || !$evaluacion->puntualidad) {
                    $estado_clasificado = "Descalificado";
                }
            }
            // Crear registro en ranking
            Ranking::create([
                'id_fase' => $faseActiva->id,
                'id_inscripcion' => $evaluacion->id_inscripcion,
                'puesto_oficial' => $posicionReal,
                'estado_final' => $estado_clasificado,
                'puntaje_total' => $evaluacion->nota,
                'observacion' => $evaluacion->descripcion,
                'publicado_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $notaAnterior = $evaluacion->nota;
            $posicion++;
        }
        return true;
    }




    public function publicarResultadosTodas($id)
    {
        try {
            $fase = Fase::find($id);

            if (!$fase) {
                return [
                    'status_code' => 404,
                    'content' => [
                        'status' => 'error',
                        'message' => "No se encuentra la fase con id $id",
                    ],
                ];
            }

            DB::transaction(function () use ($id) {
                // Publicar todas las evaluaciones de la fase
                Evaluacion::where('id_fase', $id)
                    ->update([
                        'estado_confirmacion' => 'publicado'
                    ]);

                // Publicar todos los registros de area_nivel_fase
                AreaNivelFase::where('id_fase', $id)
                    ->update([
                        'estado' => 'publicado'
                    ]);
            });

            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => "Todos los resultados de la fase fueron publicados correctamente.",
                ],
            ];

        } catch (\Exception $e) {

            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al publicar: ' . $e->getMessage(),
                ],
            ];
        }
    }


}