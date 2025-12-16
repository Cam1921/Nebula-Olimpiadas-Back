<?php

namespace App\Http\Controllers;

use App\Repositories\FaseRepository;
use App\Services\ActividadService;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar actividades.
 */
class ActividadController extends Controller
{
    protected $actividadService;
    protected $faseRepository;
    /**
     *  Constructor de la clase.
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
     * obtiene una actividad por id
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $res = $this->actividadService->obtenerActividad($id);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Actualiza una actividad
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
     * Elimina una actividad
     * @param mixed $id
     * @return void
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Verifica si una actividad existe por nombre y fase
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
     *  Obtiene las actividades por fase
     * @param mixed $faseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function porFase($faseId)
    {
        $res = $this->actividadService->getActividadesPorFase($faseId);
        return response()->json($res['content'], $res['status_code']);
    }
}
