<?php

namespace App\Http\Controllers;

use App\Services\EvaluacionesService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class EvaluacionesController extends Controller
{
    use ApiResponseTrait;
    protected $evaluacionesService;

    public function __construct(EvaluacionesService $evaluacionesService)
    {
        $this->evaluacionesService = $evaluacionesService;
    }
    public function indexByEvaluador($id)
    {
        try {
            $evaluaciones = $this->evaluacionesService->obtenerEvaluacionesPorEvaluador($id);

            return response()->json(
                $this->successResponse(
                    'Evaluaciones obtenidas correctamente.',
                    $evaluaciones->toArray()
                ),
                200
            );

        } catch (\Exception $e) {

            $code = str_contains($e->getMessage(), 'No se encontraron') ? 404 : 500;

            return response()->json(
                $this->errorResponse(
                    'ServerError',
                    $e->getMessage(),
                    [],
                    $code
                ),
                $code
            );
        }
    }
}
