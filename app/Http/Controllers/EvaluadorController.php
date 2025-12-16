<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluadorRequest;
use App\Repositories\InvitacionRepository;
use App\Services\EvaluadoresService;
use App\Services\PersonaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Clase EvaluadorController
 */
class EvaluadorController extends Controller
{
    protected $evaluadorService;
    protected $invitacionRepo;
    protected $personaService;

    /**
     * Constructor de la clase
     * @param EvaluadoresService $evaluadoresService
     * @param PersonaService $personaService
     * @param InvitacionRepository $invitacionRepo
     */
    public function __construct(EvaluadoresService $evaluadoresService, PersonaService $personaService, InvitacionRepository $invitacionRepo)
    {
        $this->evaluadorService = $evaluadoresService;
        $this->personaService = $personaService;
        $this->invitacionRepo = $invitacionRepo;
    }
    /**
     * Lista evaluadores 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $res = $this->evaluadorService->listEvaluadores($request->all());
        return response()->json($res['content'], $res['status_code']);
    }
    /**
     * Crear un nuevo evaluador
     * @param StoreEvaluadorRequest $request
     * @return JsonResponse
     */
    public function store(StoreEvaluadorRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $res = $this->evaluadorService->crearEvaluador($datos);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Actualizar un evaluador
     * @param Request $request
     * @param mixed $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $res = $this->evaluadorService->actualizarEvaluador($id, $request);
        return response()->json($res['content'], $res['status_code']);

    }

    /**
     * Eliminar evaluador
     * @param mixed $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $res = $this->evaluadorService->eliminarEvaluador($id);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Verificar el campo de un evaluador
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $res = $this->evaluadorService->checkEvaluador($request);
        return response()->json($res['content'], $res['status_code']);
    }
}
