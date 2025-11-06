<?php

namespace App\Services;

use App\Repositories\AreaNivelFaseRepository;
use App\Services\AreaNivelService;
use App\Traits\ApiResponseTrait;

class CierreFaseService
{
    use ApiResponseTrait;
    protected $areaNivelFaseRepo;
    protected $areaNivelService;

    public function __construct(AreaNivelFaseRepository $areaNivelFaseRepo, AreaNivelService $areaNivelService)
    {
        $this->areaNivelFaseRepo = $areaNivelFaseRepo;
        $this->areaNivelService = $areaNivelService;
    }
    public function getAllEstadoAreaNivelConfirmado()
    {
        $areaNivelFases = $this->areaNivelFaseRepo->getAllWithEvaluaciones();
        $resultado = [];

        foreach ($areaNivelFases as $anf) {
            $areaNivel = $anf->area_nivel;
            if ($anf->estado !== 'confirmado' || $anf->fase->nombre !== 'Clasificación') {
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

        return $this->successResponse('estados obtenidos correctamente', $resultado);
    }
}
