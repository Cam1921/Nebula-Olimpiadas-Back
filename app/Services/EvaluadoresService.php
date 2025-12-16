<?php
namespace App\Services;

use App\Models\Area;
use App\Models\Asignacion;
use App\Models\Persona;
use App\Models\PersonaArea;
use App\Repositories\InvitacionRepository;
use App\Repositories\PersonaRepository;
use App\Traits\ApiResponseTrait;
use App\Traits\NormalizeStringTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Invitacion;

class EvaluadoresService
{
    use ApiResponseTrait;
    use NormalizeStringTrait;
    protected $personaRepository;
    protected $personaService;
    protected $invitacionRepo;

    /**
     * Constructor del servicio EvaluadoresService
     * @param PersonaRepository $personaRepository
     * @param PersonaService $personaService
     * @param InvitacionRepository $invitacionRepository
     */
    public function __construct(PersonaRepository $personaRepository, PersonaService $personaService, InvitacionRepository $invitacionRepository)
    {
        $this->personaRepository = $personaRepository;
        $this->personaService = $personaService;
        $this->invitacionRepo = $invitacionRepository;
    }

    /**
     * Lista evaluadores
     * @param array $params
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function listEvaluadores(array $params)
    {
        try {
            $include = explode(',', $params['include'] ?? '');
            if (in_array('areas', $include) && !in_array('area_nivel', $include)) {
                return $this->getEvaluadoresArea($params);
            }
            if (in_array('area_nivel', $include)) {
                return $this->getEvaluadoresAreaNivel($params);
            }
            return [
                'status_code' => 201,
                'content' => [
                    'status' => 'success',
                    'message' => 'evaluadores listados',
                    'data' => [],
                    'meta' => [],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error inesperado: ' . $e->getMessage(),
                ],
            ];
        }

    }

    /**
     * Lista de evaluadores por area
     * @param array $params
     * @return array{content: array, status_code: int}
     */
    private function getEvaluadoresArea(array $params)
    {
        $areaId = $params['area_id'] ?? null;
        $search = $params['search'] ?? null;
        $perPage = $params['per_page'] ?? 10;

        $query = Persona::with([
            'user:id,email',
            'persona_areas.area:id,nombre_area',
        ])->whereHas('rols', fn($q) => $q->where('nombre', 'evaluador'));

        if ($areaId) {
            $query->whereHas('persona_areas', fn($q) => $q->where('id_area', $areaId));
        }

        if ($search) {
            $query->where(
                fn($q) =>
                $q->where('nombres', 'LIKE', "%$search%")
                    ->orWhere('apellidos', 'LIKE', "%$search%")
                    ->orWhere('ci', 'LIKE', "%$search%")
            );
        }
        $evaluadores = $query->paginate($perPage);
        $data = $evaluadores->getCollection()->transform(fn($persona) => [
            'id' => $persona->id,
            'nombre' => $persona->nombres,
            'apellidos' => $persona->apellidos,
            'ci' => $persona->ci,
            'correo' => $persona->user->email ?? null,
            'telefono' => '+591 ' . $persona->telefono,
            'area' => optional($persona->persona_areas->first()?->area)->nombre_area,
            'id_area' => optional($persona->persona_areas->first()?->area)->id,
            'fecha_registro' => optional($persona->created_at)->format('Y-m-d'),
        ]);

        $areas = $this->informacionAreas();
        $meta = [
            'current_page' => $evaluadores->currentPage(),
            'last_page' => $evaluadores->lastPage(),
            'per_page' => $evaluadores->perPage(),
            'total' => $evaluadores->total(),
            'total_evaluadores' => $areas['totalEvaluadores'],
            'areas_cubiertas' => $areas['areasCubiertas'],
            'areas_disponibles' => $areas['areasDisponibles'],
            'areas' => $areas['areas'],
        ];

        return [
            'status_code' => 201,
            'content' => [
                'status' => 'success',
                'message' => 'evaluadores listados',
                'data' => $data,
                'meta' => $meta,
            ],

        ];
    }

    /**
     * Lista de evaluadores por area y nivel
     * @param array $params
     * @return array{content: array, status_code: int}
     */
    private function getEvaluadoresAreaNivel(array $params)
    {
        $areaId = $params['area_id'] ?? null;
        $nivelId = $params['nivel_id'] ?? null;
        $search = $params['search'] ?? null;
        $perPage = $params['per_page'] ?? 10;

        $query = Asignacion::with([
            'persona:id,nombres,apellidos,ci,telefono,email',
            'persona.rols:id,nombre',
            'area_nivel.area:id,nombre_area',
            'area_nivel.nivel:id,nombre_nivel'
        ])->whereHas('persona.rols', fn($q) => $q->where('nombre', 'evaluador'));

        if ($areaId) {
            $query->whereHas('area_nivel', fn($q) => $q->where('id_area', $areaId));
        }

        if ($nivelId) {
            $query->whereHas('area_nivel', fn($q) => $q->where('id_nivel', $nivelId));
        }

        if ($search) {
            $query->whereHas(
                'persona',
                fn($q) =>
                $q->where('nombres', 'LIKE', "%$search%")
                    ->orWhere('apellidos', 'LIKE', "%$search%")
                    ->orWhere('ci', 'LIKE', "%$search%")
            );
        }
        $evaluadores = $query->paginate($perPage);
        $data = $evaluadores->getCollection()->transform(fn($asignacion) => [
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
        ]);
        $meta = [
            'current_page' => $evaluadores->currentPage(),
            'last_page' => $evaluadores->lastPage(),
            'per_page' => $evaluadores->perPage(),
            'total' => $evaluadores->total(),
        ];
        return [
            'status_code' => 201,
            'content' => [
                'status' => 'success',
                'message' => 'evaluadores listados',
                'data' => $data,
                'meta' => $meta,
            ],

        ];
    }

    /**
     * Crea un evaluador
     * @param mixed $datos
     */
    public function crearEvaluador($datos)
    {
        try {
            return DB::transaction(function () use ($datos) {
                $rolEvaluador = Rol::where('nombre', 'evaluador')->firstOrFail();
                try {
                    $user = User::create([
                        'name' => "{$datos['nombre']} {$datos['apellidos']}",
                        'email' => $datos['email'],
                        'password' => Hash::make(Str::random(16)), // 🔹 aquí
                    ]);
                } catch (\Exception $e) {
                    return [
                        'status_code' => 500,
                        'content' => [
                            'status' => 'error',
                            'message' => 'Error al crear el usuario: ' . $e->getMessage(),
                        ],
                    ];
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
                    return [
                        'status_code' => 422,
                        'content' => [
                            'status' => 'error',
                            'message' => 'Área no existen en la tabla area.'
                        ],
                    ];
                }
                $asignacionesArea = PersonaArea::where('id_area', $area->id)->count();
                $cantidadEvalArea = $area->cantidad_evaluadores;


                if ($asignacionesArea >= $cantidadEvalArea) {

                    return [
                        'status_code' => 422,
                        'content' => [
                            'status' => 'error',
                            'message' => 'La cantidad de evaluadores ya se alcanzó.'
                        ],
                    ];
                }
                PersonaArea::create([
                    'id_area' => $area->id,
                    'id_persona' => $persona->id,
                ]);
                $this->personaService->enviarCorreoCreacionPassword($persona->id);

                return [
                    'status_code' => 201,
                    'content' => [
                        'status' => 'success',
                        'message' => 'evaluadores creado correctamente',
                    ],

                ];
            });
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error inesperado: ' . $e->getMessage(),
                ],
            ];
        }

    }


    /**
     * Actualiza un evaluador
     * @param mixed $id
     * @param mixed $request
     * @throws \Exception
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function actualizarEvaluador($id, $request)
    {
        try {
            $persona = Persona::with('user', 'rols')->find($id);
            if (!$persona) {
                return [
                    'status_code' => 422,
                    'content' => [
                        'status' => 'error',
                        'message' => 'Persona no encontrada',
                    ],
                ];
            }
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


            DB::transaction(function () use ($request, $persona) {
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

                    $persona->asignacions()->delete();
                    $persona->persona_areas()->delete();
                    $Areaexists = Area::findOrFail($request->id_area);

                    $asignacionesArea = PersonaArea::where('id_area', $Areaexists->id)
                        ->whereHas('persona.rols', function ($q) {
                            $q->where('nombre', 'evaluador');
                        })->count();
                    $cantidadEvalArea = $Areaexists->cantidad_evaluadores;

                    if ($asignacionesArea >= $cantidadEvalArea) {
                        throw new \Exception('La cantidad de evaluadores ya se alcanzó.');
                    }
                    $persona->persona_areas()->create([
                        'id_area' => $Areaexists->id,
                    ]);
                }
                $persona->load(['user', 'persona_areas.area']);

            });
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Evaluador actualizado correctamente.',
                ],
            ];

        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status_code' => 422,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error de validación',
                    'errors' => $e->errors(),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al actualizar evaluador.' . $e->getMessage(),
                ],
            ];
        }
    }
    private function informacionAreas()
    {
        $areas = Area::all(); // traemos todas las áreas
        $areasInfo = [];
        $totalEvaluadores = 0;
        $areasCubiertas = 0;

        foreach ($areas as $area) {
            $taken = PersonaArea::where('id_area', $area->id)
                ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'evaluador'))->count();
            $available = $area->cantidad_evaluadores - $taken;

            // contar total de evaluadores
            $totalEvaluadores += $taken;

            // si ya hay al menos un evaluador, el área se considera cubierta
            if ($taken >= $area->cantidad_evaluadores) {
                $areasCubiertas++;
            }

            $areasInfo[] = [
                'id' => $area->id,
                'nombre' => $area->nombre_area,
                'max' => $area->cantidad_evaluadores,
                'ocupados' => $taken,
                'faltantes' => $available,
            ];
        }

        $areasDisponibles = $areas->count() - $areasCubiertas;

        return [
            'areas' => $areasInfo,
            'totalEvaluadores' => $totalEvaluadores,
            'areasCubiertas' => $areasCubiertas,
            'areasDisponibles' => $areasDisponibles,
        ];
    }

    /**
     * Elimina un evaluador
     * @param mixed $id
     * @return array{content: array{message: string, status: string, status_code: int}}
     */
    public function eliminarEvaluador($id)
    {
        try {
            $persona = Persona::with('user', 'asignacions', 'persona_areas')->findOrFail($id);

            DB::transaction(function () use ($persona) {

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
            });
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Evaluador eliminado correctamente.',
                ],
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Evaluador no encontrado.',
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al eliminar evaluador: ' . $e->getMessage(),
                ],
            ];
        }

    }
    public function checkEvaluador($request)
    {
        try {
            $field = $request->query('field');
            $value = $request->query('value');
            $excludeId = $request->query('excludeId'); // ← Nueva línea

            if (!in_array($field, ['ci', 'telefono', 'email'])) {
                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'message' => 'Campo no permitido',
                    ],
                ];
            }

            $exists = match ($field) {
                'email' => User::where('email', $value)->exists(),
                'ci', 'telefono' => Persona::where($field, $value)->exists(),
            };

            return [
                'status_code' => 200,
                'content' => [

                    'status' => 'success',
                    'exists' => $exists,
                    'message' => 'Campo verificado correctamente.',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error inesperado: ' . $e->getMessage(),
                ],
            ];
        }

    }

}