<?php

namespace App\Services;

use App\Models\Evaluacion;
use App\Repositories\EvaluacionRepository;
use App\Traits\ApiResponseTrait;

class EvaluacionesService
{


    protected $evaluacionRepository;
    public function __construct(EvaluacionRepository $evaluacionRepository)
    {
        $this->evaluacionRepository = $evaluacionRepository;
    }
    public function obtenerEvaluacionesPorEvaluador($idEvaluador)
    {
        $evaluaciones = $this->evaluacionRepository->obtenerEvaluacionesPorEvaluador($idEvaluador);

        if ($evaluaciones->isEmpty()) {
            throw new \Exception('No se encontraron evaluaciones para este evaluador.');
        }

        return $evaluaciones;
    }
}
