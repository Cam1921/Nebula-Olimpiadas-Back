<?php

namespace App\Http\Controllers;

use App\Repositories\FaseRepository;
use App\Services\ActividadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Summary of ActividadController
 */
class ActividadController extends Controller
{
    protected $actividadService;
    protected $faseRepository;
    /**
     * Summary of __construct
     * @param ActividadService $actividadService
     */
    public function __construct(ActividadService $actividadService, FaseRepository $faseRepository)
    {
        $this->actividadService = $actividadService;
        $this->faseRepository = $faseRepository;
    }

    /**
     * Muestra la lista de actividades
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $res = $this->actividadService->listarActividades();
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Summary of store
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Summary of show
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $res = $this->actividadService->obtenerActividad($id);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Summary of update
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $res = $this->actividadService->actualizarActividad($id, $request->all());
        return response()->json($res['content'], $res['status_code']);

    }

    /**
     * Summary of destroy
     * @param mixed $id
     * @return void
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Summary of verificarActividad
     * @param mixed $nombreFase
     * @param mixed $nombreActividad
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarActividad($nombreFase, $nombreActividad)
    {
        $res = $this->actividadService->verificarActividadPorNombreYFase($nombreFase, $nombreActividad);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Summary of porFase
     * @param mixed $faseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function porFase($faseId)
    {
        $res = $this->actividadService->getActividadesPorFase($faseId);
        return response()->json($res['content'], $res['status_code']);
    }
}
