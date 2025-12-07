<?php
// app/Http/Controllers/ResponsableAcademicoController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreResponsableAcademicoRequest;
use App\Models\Area;
use App\Models\AreaNivel;
use App\Models\Asignacion;
use App\Models\Invitacion;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use App\Repositories\InvitacionRepository;
use App\Services\EvaluadoresService;
use App\Services\PersonaService;
use App\Services\ResponsableService;
use DB;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class ResponsableAcademicoController extends Controller
{

    protected $invitacionRepo;

    protected $evaluadorService;
    protected $personaService;
    protected $responsableService;

    public function __construct(EvaluadoresService $evaluadoresService, PersonaService $personaService, InvitacionRepository $invitacionRepo, ResponsableService $responsableService)
    {
        $this->evaluadorService = $evaluadoresService;
        $this->personaService = $personaService;
        $this->invitacionRepo = $invitacionRepo;
        $this->responsableService = $responsableService;
    }

    /**
     * Obtener todos los evaluadores         
     */
    public function index(): JsonResponse
    {
        $res = $this->responsableService->getResponsablesAcademicos();

        return response()->json($res['content'], $res['status_code']);
    }
    /**
     * Crear nuevo evaluador
     */
    public function store(StoreResponsableAcademicoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $res = $this->responsableService->crearResponsableAcademico($validated);
        return response()->json($res['content'], $res['status_code']);
    }
    /**
     * Actualizar datos de un evaluador
     */
    public function update(Request $request, $id): JsonResponse
    {

        $res = $this->responsableService->actualizarResponsableAcademico($id, $request);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Eliminar un evaluador
     */
    public function destroy($id): JsonResponse
    {
        $res = $this->responsableService->eliminarResponsableAcademico($id);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Verificar existencia de campos únicos
     */
    public function check(Request $request): JsonResponse
    {
        $field = $request->query('field');
        $value = $request->query('value');
        $excludeId = $request->query('excludeId'); // ← Nueva línea

        if (!in_array($field, ['ci', 'telefono', 'email'])) {
            return response()->json(['error' => 'Campo no permitido'], 400);
        }

        $exists = match ($field) {
            'email' => User::where('email', $value)->exists(),
            'ci', 'telefono' => Persona::where($field, $value)->exists(),
        };

        return response()->json(['exists' => $exists]);
    }
}