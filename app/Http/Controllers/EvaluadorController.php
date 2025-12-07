<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluadorRequest;
use App\Models\Area;
use App\Models\AreaNivel;
use App\Models\Invitacion;
use App\Models\Persona;
use App\Models\Asignacion;
use App\Models\PersonaArea;
use App\Models\Rol;
use App\Models\User;
use App\Repositories\InvitacionRepository;
use App\Services\EvaluadoresService;
use App\Services\PersonaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EvaluadorController extends Controller
{
    protected $evaluadorService;
    protected $invitacionRepo;
    protected $personaService;

    public function __construct(EvaluadoresService $evaluadoresService, PersonaService $personaService, InvitacionRepository $invitacionRepo)
    {
        $this->evaluadorService = $evaluadoresService;
        $this->personaService = $personaService;
        $this->invitacionRepo = $invitacionRepo;
    }

    /*  public function get_evaluadores_area(Request $request): JsonResponse
     {

         $areaId = $request->input('area_id');
         $search = $request->input('search');
         $perPage = $request->input('per_page', 10);

         $query = Persona::with([
             'user:id,email',
             'persona_areas.area:id,nombre_area',
         ])
             ->whereHas('rols', fn($q) => $q->where('nombre', 'evaluador'));

         //  FILTRO POR ÁREA (si se envía area_id)
         if ($areaId) {
             $query->whereHas('persona_areas', function ($q) use ($areaId) {
                 $q->where('id_area', $areaId);
             });
         }

         //  BÚSQUEDA POR NOMBRE, APELLIDO O CI
         if ($search) {
             $query->where(function ($q) use ($search) {
                 $q->where('nombres', 'LIKE', "%$search%")
                     ->orWhere('apellidos', 'LIKE', "%$search%")
                     ->orWhere('ci', 'LIKE', "%$search%");
             });
         }


         $evaluadores = $query->paginate($perPage);


         $evaluadores->getCollection()->transform(function ($persona) {
             return [
                 'id' => $persona->id,
                 'nombre' => $persona->nombres,
                 'apellidos' => $persona->apellidos,
                 'ci' => $persona->ci,
                 'correo' => $persona->user->email ?? null,
                 'telefono' => '+591 ' . $persona->telefono,
                 'area' => optional($persona->persona_areas->first()?->area)->nombre_area,
                 'id_area' => optional($persona->persona_areas->first()?->area)->id,

                 'fecha_registro' => optional($persona->created_at)->format('Y-m-d'),
             ];
         });
         $meta = [
             'current_page' => $evaluadores->currentPage(),
             'last_page' => $evaluadores->lastPage(),
             'per_page' => $evaluadores->perPage(),
         ];
         $data = $evaluadores->items();

         return response()->json([
             'status' => 'success',
             'data' => $data,
             'meta' => $meta
         ]);
     }

     public function get_evaluadores_areaNivel(Request $request): JsonResponse
     {

         $areaId = $request->input('area_id');
         $nivelId = $request->input('nivel_id');
         $search = $request->input('search');
         $perPage = $request->input('per_page', 10);

         $query = Asignacion::with([
             'persona:id,nombres,apellidos,ci,telefono,email',
             'persona.rols:id,nombre',
             'area_nivel.area:id,nombre_area',
             'area_nivel.nivel:id,nombre_nivel'
         ])
             ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'evaluador'));

         //  FILTRO POR ÁREA (si se envía area_id)
         if ($areaId) {
             $query->whereHas('area_nivel', function ($q) use ($areaId) {
                 $q->where('id_area', $areaId);
             });
         }

         //  FILTRO POR NIVEL (si se envía nivel_id)
         if ($nivelId) {
             $query->whereHas('area_nivel', function ($q) use ($nivelId) {
                 $q->where('id_nivel', $nivelId);
             });
         }
         //  BÚSQUEDA POR NOMBRE, APELLIDO O CI
         if ($search) {
             $query->whereHas('persona', function ($q) use ($search) {
                 $q->where('nombres', 'LIKE', "%$search%")
                     ->orWhere('apellidos', 'LIKE', "%$search%")
                     ->orWhere('ci', 'LIKE', "%$search%");
             });
         }


         $evaluadores = $query->paginate($perPage);
         Log::debug($evaluadores);

         $evaluadores->getCollection()->transform(function ($asignacion) {
             return [
                 'id_asignacion' => $asignacion->id,
                 'id' => $asignacion->id_persona,
                 'nombre' => $asignacion->persona->nombres,
                 'apellidos' => $asignacion->persona->apellidos,
                 'ci' => $asignacion->persona->ci,
                 'correo' => $asignacion->persona->email ?? null,
                 'telefono' => '+591 ' . $asignacion->persona->telefono,
                 'area' => $asignacion->area_nivel->area->nombre_area,
                 'id_area' => $asignacion->area_nivel->area->id,
                 'nivel' => $asignacion->area_nivel->nivel->nombre_nivel,
                 'id_nivel' => $asignacion->area_nivel->nivel->id,
                 'fecha_registro' => optional($asignacion->created_at)->format('Y-m-d'),
             ];
         });
         $meta = [
             'current_page' => $evaluadores->currentPage(),
             'last_page' => $evaluadores->lastPage(),
             'per_page' => $evaluadores->perPage(),
         ];
         $data = $evaluadores->items();

         return response()->json([
             'status' => 'success',
             'data' => $data,
             'meta' => $meta
         ]);
     }
  */
    public function index(Request $request): JsonResponse
    {
        $res = $this->evaluadorService->listEvaluadores($request->all());
        return response()->json($res['content'], $res['status_code']);
    }
    public function store(StoreEvaluadorRequest $request): JsonResponse
    {
        $datos = $request->validated();

        return DB::transaction(function () use ($datos) {
            $rolEvaluador = Rol::where('nombre', 'evaluador')->firstOrFail();



            try {
                $user = User::create([
                    'name' => "{$datos['nombre']} {$datos['apellidos']}",
                    'email' => $datos['email'],
                    'password' => Hash::make(Str::random(16)), // 🔹 aquí
                ]);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'message' => 'Error al crear el usuario: ' . $e->getMessage()], 500);
            }



            $persona = Persona::create([
                'nombres' => $datos['nombre'],
                'apellidos' => $datos['apellidos'],
                'ci' => $datos['ci'],
                'telefono' => $datos['telefono'],
                'email' => $datos['email'],
                'id_usuario' => $user->id,
            ]);


            $persona->rols()->attach($rolEvaluador->id);

            $area = Area::where('id', $datos['id_area'])->first();

            if (!$area) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Área no existen en la tabla area.'
                ], 422);
            }
            $asignacionesArea = PersonaArea::where('id_area', $area->id)->count();
            $cantidadEvalArea = $area->cantidad_evaluadores;


            if ($asignacionesArea >= $cantidadEvalArea) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La cantidad de evaluadores ya se alcanzó.'
                ], 422);
            }
            PersonaArea::create([
                'id_area' => $area->id,
                'id_persona' => $persona->id,
            ]);
            $this->personaService->enviarCorreoCreacionPassword($persona->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Evaluador registrado correctamente.',
            ], 201);
        });
    }

    public function update(Request $request, $id): JsonResponse
    {

        try {
            $persona = Persona::with('user', 'rols')->find($id);
            if (!$persona) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Persona no encontrada',
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener la persona ',
            ]);
        }


        try {

            $request->validate([
                'nombre' => 'sometimes|string|min:2',
                'apellidos' => 'sometimes|string|min:2',
                'ci' => "sometimes|numeric|unique:persona,ci,{$id},id",
                'telefono' => "sometimes|string|size:8|regex:/^[67]\d{7}$/|unique:persona,telefono,{$id},id",
                'email' => "sometimes|email|unique:persona,email,{$id},id",
                'id_area' => 'required|integer|exists:area,id',

            ], [

                'nombre.string' => 'El nombre debe ser un texto válido.',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',

                'apellidos.string' => 'Los apellidos deben ser un texto válido.',
                'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres.',

                'ci.numeric' => 'El CI debe ser un número.',
                'ci.unique' => 'Este CI ya se encuentra registrado.',

                'telefono.size' => 'El teléfono debe tener exactamente 8 dígitos.',
                'telefono.regex' => 'El teléfono debe comenzar con 6 o 7 y contener 8 dígitos.',
                'telefono.unique' => 'Este teléfono ya está registrado.',

                'email.email' => 'Debe ingresar un correo electrónico válido.',
                'email.unique' => 'Este correo electrónico ya está registrado.',

                'id_area.required_with' => 'Debe indicar el área en cada asignación.',
                'id_area.integer' => 'El ID del área debe ser un número válido.',
                'id_area.exists' => 'El área seleccionada no existe.',

            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
        Log::debug('Iniciando control de duplicados', [$request->all()]);
        try {
            return DB::transaction(function () use ($request, $persona) {
                $oldEmail = $persona->email;
                $data = $request->only(['nombre', 'apellidos', 'ci', 'telefono', 'email']);
                if (!empty($data)) {
                    $persona->update([
                        'nombres' => $data['nombre'] ?? $persona->nombres,
                        'apellidos' => $data['apellidos'] ?? $persona->apellidos,
                        'ci' => $data['ci'] ?? $persona->ci,
                        'telefono' => $data['telefono'] ?? $persona->telefono,
                        'email' => $data['email'] ?? $persona->email,
                    ]);
                }
                if ($persona->user) {
                    $userData = [];
                    if ($request->filled('email')) {
                        $userData['email'] = $request->email;
                    }
                    if (!empty($userData)) {
                        $persona->user->update($userData);
                    }
                    if ($request->filled('email') && $oldEmail !== $request->email) {
                        $invitacion = Invitacion::where('email', $oldEmail)->first();
                        if ($invitacion) {
                            $invitacion->update(['email' => $request->email]);
                        }
                    }
                }
                if ($request->filled('id_area')) {

                    try {
                        $persona->asignacions()->delete();
                        $persona->persona_areas()->delete();
                    } catch (\Throwable $th) {
                        return response()->json([
                            'status' => 'error',
                            'error' => 'error al eliminar la asignación.'
                        ], 422);
                    }
                    $area = $request->only(['id_area']);
                    $Areaexists = Area::find($area['id_area']);

                    Log::debug('area', [$area]);

                    if (!$Areaexists) {
                        return response()->json([
                            'status' => 'error',
                            'error' => 'Área no existen en la tabla area.'
                        ], 422);
                    }

                    $asignacionesArea = PersonaArea::where('id_area', $Areaexists->id)->count();
                    $cantidadEvalArea = $Areaexists->cantidad_evaluadores;
                    Log::debug('asignacionesArea', [$asignacionesArea, $cantidadEvalArea]);

                    if ($asignacionesArea >= $cantidadEvalArea) {
                        return response()->json([
                            'status' => 'error',
                            'error' => 'La cantidad de evaluadores ya se alcanzó.'
                        ], 422);
                    }

                    $persona->persona_areas()->create([
                        'id_area' => $Areaexists->id,
                    ]);
                }
                $persona->load(['user', 'persona_areas.area']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Evaluador actualizado correctamente.',

                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar evaluador.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar un evaluador
     */
    public function destroy($id): JsonResponse
    {
        $persona = Persona::with('user', 'asignacions', 'persona_areas')->findOrFail($id);

        return DB::transaction(function () use ($persona) {

            $persona->asignacions()->delete();

            // Eliminar relaciones con persona_area
            $persona->persona_areas()->delete();

            // Eliminar roles asociados (si usas pivot)
            $persona->rols()->detach();

            // Eliminar usuario
            $persona->user()->delete();

            // Eliminar persona
            $persona->delete();

            // Eliminar invitación si existe
            $invitacion = $this->invitacionRepo->findByEmail($persona->email);
            if ($invitacion) {
                $this->invitacionRepo->delete($invitacion);
            }

            return response()->json([
                'message' => 'Evaluador eliminado correctamente.',
            ]);
        });
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
