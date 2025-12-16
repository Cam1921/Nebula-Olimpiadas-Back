<?php

namespace App\Services;

use App\Models\Area;
use App\Models\AreaNivel;
use App\Models\Asignacion;
use App\Models\Invitacion;
use App\Models\Persona;
use App\Models\PersonaArea;
use App\Models\Rol;
use App\Models\User;
use App\Repositories\InvitacionRepository;
use DB;
use Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResponsableService
{
    protected $personaService;
    protected $invitacionRepo;
    /**
     * Create a new class instance.
     */
    public function __construct(PersonaService $personaService, InvitacionRepository $invitacionRepo)
    {
        $this->personaService = $personaService;
        $this->invitacionRepo = $invitacionRepo;
    }

    /**
     * Obtener todos los responsables academicos
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function getResponsablesAcademicos()
    {
        try {
            $evaluadores = Persona::with([
                'user:id,email',
                'persona_areas.area:id,nombre_area',
            ])
                ->whereHas('rols', fn($q) => $q->where('nombre', 'responsable'))
                ->get()
                ->map(function ($persona) {
                    return [
                        'id' => $persona->id,
                        'nombre' => $persona->nombres,
                        'apellidos' => $persona->apellidos,
                        'ci' => $persona->ci,
                        'correo' => $persona->user->email ?? null,
                        'telefono' => '+591 ' . $persona->telefono,
                        'area' => $persona->persona_areas->first()->area->nombre_area,
                        'id_area' => $persona->persona_areas->first()->id_area,
                        'fecha_registro' => optional($persona->created_at)->format('Y-m-d'),
                    ];
                });
            $meta = $this->informacionAreas();
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Responsables obtenidos correctamente',
                    'data' => $evaluadores,
                    'meta' => $meta
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al obtener los responsables: ' . $e->getMessage()
                ]
            ];
        }


    }
    /**
     * Información de áreas
     * @return array{areas: \Illuminate\Database\Eloquent\Collection<int, array>|\Illuminate\Support\Collection<int, array>, areasCubiertas: int, areasDisponibles: int, totalResponsables: int}
     */
    private function informacionAreas()
    {
        $areas = Area::all();
        $cantidad_areas = $areas->count();
        $areasCubiertas = PersonaArea::whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))->get()
            ->map(function ($area) {
                return [
                    'id' => $area->id_area,
                    'nombre' => $area->area->nombre_area
                ];
            });
        $taken = $areasCubiertas->count();
        $available = $cantidad_areas - $taken;
        $totalResponsables = $taken;

        return [
            'totalResponsables' => $totalResponsables,
            'areasCubiertas' => $taken,
            'areasDisponibles' => $available,
            'areas' => $areasCubiertas
        ];
    }

    /**
     * Crear un nuevo responsable academico
     * @param mixed $validated
     * @throws \Exception
     * @return array{content: array{message: string, status: string, status_code: int}}
     */
    public function crearResponsableAcademico($validated)
    {
        try {
            DB::transaction(function () use ($validated) {
                $rolResponsable = Rol::where('nombre', 'responsable')->firstOrFail();
                $user = User::create([
                    'name' => "{$validated['nombre']} {$validated['apellidos']}",
                    'email' => $validated['email'],
                    'password' => Hash::make(Str::random(16)),
                ]);
                $persona = Persona::create([
                    'nombres' => $validated['nombre'],
                    'apellidos' => $validated['apellidos'],
                    'ci' => $validated['ci'],
                    'telefono' => $validated['telefono'],
                    'email' => $validated['email'],
                    'id_usuario' => $user->id,
                ]);
                $persona->rols()->attach($rolResponsable->id);
                $area = Area::findOrFail($validated['id_area']);
                $areaNiveles = AreaNivel::where('id_area', $area->id)->get();

                if ($areaNiveles->isEmpty()) {
                    throw new \Exception('No se encontraron niveles asociados a esta área.');
                }
                $existsPersonaArea = PersonaArea::where('id_area', $validated['id_area'])
                    ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                    ->exists();
                if ($existsPersonaArea) {
                    throw new \Exception('Ya existe un Responsable asignado a esta área.');
                }
                PersonaArea::create([
                    'id_persona' => $persona->id,
                    'id_area' => $validated['id_area'],
                ]);
                foreach ($areaNiveles as $areaNivel) {

                    $exists = Asignacion::where('id_area_nivel', $areaNivel->id)
                        ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                        ->exists();


                    if ($exists) {
                        throw new \Exception('Ya existe un Responsable asignado en uno de los niveles del área.');
                    }

                    Asignacion::create([
                        'id_persona' => $persona->id,
                        'id_area_nivel' => $areaNivel->id,
                    ]);
                }

                $this->personaService->enviarCorreoCreacionPassword($persona->id);

                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel']);
            });
            return [
                'status_code' => 201,
                'content' => [
                    'status' => 'success',
                    'message' => 'Responsable académico registrado correctamente.',
                ]
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Área o rol no encontrado.',
                ]
            ];

        } catch (\Throwable $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]
            ];
        }

    }
    /**
     * Actualizar un responsable academico
     * @param mixed $id
     * @param mixed $request
     * @throws \Exception
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function actualizarResponsableAcademico($id, $request)
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
            Log::debug("datos a actualizar: " . json_encode($request->all()));
            $request->validate([
                'nombre' => 'sometimes|string|min:2',
                'apellidos' => 'sometimes|string|min:2',
                'ci' => "sometimes|numeric|unique:persona,ci,{$id},id",
                'telefono' => "sometimes|string|size:8|regex:/^[67]\d{7}$/|unique:persona,telefono,{$id},id",
                'email' => "sometimes|email|unique:persona,email,{$id},id",
                'id_area' => 'sometimes|integer|exists:area,id',
            ], [
                'nombre.string' => 'El nombre debe ser un texto válido.',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
                'apellidos.string' => 'Los apellidos deben ser un texto válido.',
                'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres.',
                'ci.numeric' => 'El CI debe ser un número.',
                'ci.unique' => 'Este CI ya está registrado.',
                'telefono.size' => 'El teléfono debe tener exactamente 8 dígitos.',
                'telefono.regex' => 'El teléfono debe empezar con 6 o 7 y contener 8 dígitos.',
                'telefono.unique' => 'Este teléfono ya está registrado.',
                'email.email' => 'Debe ingresar un correo válido.',
                'email.unique' => 'Este correo ya está registrado.',
                'id_area.integer' => 'El ID del área debe ser un número entero.',
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

                    $area = Area::findOrFail($request->id_area);

                    $existsPersonaArea = PersonaArea::where('id_area', $area->id)
                        ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                        ->exists();

                    if ($existsPersonaArea) {
                        throw new \Exception('Ya existe un Responsable asignado a esta área.');
                    }
                    PersonaArea::create([
                        'id_persona' => $persona->id,
                        'id_area' => $area->id,
                    ]);

                    $areaNiveles = AreaNivel::where('id_area', $area->id)->get();

                    if ($areaNiveles->isEmpty()) {
                        throw new \Exception('No se encontraron niveles asociados a esta área.');
                    }

                    foreach ($areaNiveles as $areaNivel) {
                        $exists = Asignacion::where('id_area_nivel', $areaNivel->id)
                            ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                            ->where('id_persona', '!=', $persona->id)
                            ->exists();

                        if ($exists) {
                            throw new \Exception("Ya existe un Responsable asignado en el nivel {$areaNivel->id}.");
                        }
                        Asignacion::create([
                            'id_persona' => $persona->id,
                            'id_area_nivel' => $areaNivel->id,
                        ]);
                    }

                }

                // Recargar relaciones
                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel', 'persona_areas.area']);


            });
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Responsable actualizado correctamente.',

                ],

            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Área o persona no encontrada.',
                ],
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status_code' => 422,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error de validación.',
                    'errors' => $e->errors(),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al actualizar responsable: ' . $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Eliminar un responsable academico
     * @param mixed $id
     * @return array{content: array{message: string, status: string, status_code: int}}
     */
    public function eliminarResponsableAcademico($id)
    {
        try {

            $persona = Persona::with('user', 'asignacions')->findOrFail($id);

            DB::transaction(function () use ($persona) {
                $persona->asignacions()->delete();
                $persona->persona_areas()->delete();
                $persona->user()->delete();
                $persona->delete();
                $invitacion = $this->invitacionRepo->findByEmail($persona->email);
                $this->invitacionRepo->delete($invitacion);

            });
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Responsable eliminado correctamente.',
                ],

            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Responsable no encontrado.',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al eliminar el responsable: ' . $e->getMessage(),
                ],
            ];
        }

    }

    /**
     * chequear existencia de un campo unico
     * @param mixed $request
     * @return array{content: array{exists: mixed, message: string, status: string, status_code: int}|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function checkResponsable($request)
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
                    'message' => 'Error al verificar el responsable: ' . $e->getMessage(),
                ],
            ];

        }

    }
}
