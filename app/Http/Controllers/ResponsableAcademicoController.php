<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreResponsableAcademicoRequest;
use App\Repositories\InvitacionRepository;
use App\Services\EvaluadoresService;
use App\Services\PersonaService;
use App\Services\ResponsableService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Clase ResponsableAcademicoController
 */
class ResponsableAcademicoController extends Controller
{

    protected $invitacionRepo;
    protected $evaluadorService;
    protected $personaService;
    protected $responsableService;

    /**
     * Constructor de la clase
     * @param EvaluadoresService $evaluadoresService
     * @param PersonaService $personaService
     * @param InvitacionRepository $invitacionRepo
     * @param ResponsableService $responsableService
     */
    public function __construct(EvaluadoresService $evaluadoresService, PersonaService $personaService, InvitacionRepository $invitacionRepo, ResponsableService $responsableService)
    {
        $this->evaluadorService = $evaluadoresService;
        $this->personaService = $personaService;
        $this->invitacionRepo = $invitacionRepo;
        $this->responsableService = $responsableService;
    }
    /**
     * Lista responsables academicos
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $res = $this->responsableService->getResponsablesAcademicos();
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Crear nuevo evaluador
     * @param StoreResponsableAcademicoRequest $request
     * @return JsonResponse
     */
    public function store(StoreResponsableAcademicoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $res = $this->responsableService->crearResponsableAcademico($validated);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Actualizar datos de un evaluador
     * @param Request $request
     * @param mixed $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $res = $this->responsableService->actualizarResponsableAcademico($id, $request);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Eliminar un evaluador
     * @param mixed $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $res = $this->responsableService->eliminarResponsableAcademico($id);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Verificar existencia de campos únicos
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $res = $this->responsableService->checkResponsable($request);
        return response()->json($res['content'], $res['status_code']);
    }
}