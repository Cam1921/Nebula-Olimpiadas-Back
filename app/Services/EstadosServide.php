<?php

namespace App\Services;

use App\Repositories\AreaNivelFaseRepository;
use App\Traits\ApiResponseTrait;

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

}