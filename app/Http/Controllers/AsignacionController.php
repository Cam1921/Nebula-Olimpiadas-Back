<?php

namespace App\Http\Controllers;
use App\Services\AsignacionService;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;


class AsignacionController extends Controller
{
    use ApiResponseTrait;
    protected $asignacionService;
    public function __construct(AsignacionService $asignacionService)
    {
        $this->asignacionService = $asignacionService;
    }
    /**
     * Listar asignaciones de evaluadores
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listar(Request $request)
    {
        $res = $this->asignacionService->listarAsignaciones($request);
        return response()->json($res['content'], $res['status_code']);
    }
    /**
     * Asignar inscritos a evaluadores
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function asignarInscritos(Request $request)
    {
        $res = $this->asignacionService->asignarCompetidores($request);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Asignar evaluadores a un area-nivel
     * @param Request $request
     * @param mixed $idAreaNivel
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $idAreaNivel)
    {
        $evaluadores = $request->input('evaluadores');
        $res = $this->asignacionService->asignarEvaluadores($evaluadores, $idAreaNivel);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Eliminar evaluadores de un area-nivel
     * @param Request $request
     * @param mixed $idAreaNivel
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $idAreaNivel)
    {
        $evaluadores = $request->input('asignaciones');
        $res = $this->asignacionService->eliminarEvaluadores($evaluadores, $idAreaNivel);
        return response()->json($res['content'], $res['status_code']);
    }
}
