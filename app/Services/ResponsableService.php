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


    public function crearResponsableAcademico($validated)
    {
        return DB::transaction(function () use ($validated) {
            try {

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
                $area = Area::find($validated['id_area']);

                if (!$area) {
                    return [
                        'status_code' => 422,
                        'content' => [
                            'status' => 'error',
                            'message' => ' Área no encontrada.',
                        ]
                    ];
                }

                $areaNiveles = AreaNivel::where('id_area', $validated['id_area'])->get();

                if ($areaNiveles->isEmpty()) {
                    return [
                        'status_code' => 422,
                        'content' => [
                            'status' => 'error',
                            'message' => ' No se encontraron niveles asociados a esta área.',
                        ]
                    ];
                }
                $existsPersonaArea = PersonaArea::where('id_area', $validated['id_area'])
                    ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                    ->exists();
                if ($existsPersonaArea) {
                    return [
                        'status_code' => 422,
                        'content' => [
                            'status' => 'error',
                            'message' => ' Ya existe un Responsable asignado a esta área.',
                        ]
                    ];
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
                        return [
                            'status_code' => 422,
                            'content' => [
                                'status' => 'error',
                                'message' => 'Error: Ya existe un Responsable asignado a esta área',
                                'errors' => [
                                    'asignaciones' => [
                                        "Área {$areaNivel->id_area}  ya tienen un Responsable asignado."
                                    ]
                                ]
                            ]
                        ];

                    }

                    Asignacion::create([
                        'id_persona' => $persona->id,
                        'id_area_nivel' => $areaNivel->id,
                    ]);
                }

                $resCorreo = $this->personaService->enviarCorreoCreacionPassword($persona->id);

                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel']);

                return [
                    'status_code' => 201,
                    'content' => [
                        'status' => 'success',
                        'message' => 'reponsable registrado correctamente.',
                    ]
                ];

            } catch (\Exception $e) {
                return [
                    'status_code' => 500,
                    'content' => [
                        'status' => 'error',
                        'message' => 'Error interno al registrar evaluador. ',
                    ]
                ];
            }
        });
    }

    public function actualizarResponsableAcademico($id, $request)
    {
        $persona = Persona::with('user', 'rols')->findOrFail($id);

        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status_code' => 422,
                'content' => [
                    'message' => 'Error de validación',
                    'errors' => $e->errors(),
                ]
            ];
        }

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
                    $persona->asignacions()->delete();
                    $persona->persona_areas()->delete();

                    $area = $request->only(['id_area']);
                    $Areaexists = Area::find($area['id_area']);

                    if (!$Areaexists) {
                        return [
                            'status_code' => 422,
                            'content' => [
                                'status' => 'error',
                                'message' => 'La área no existe.',
                            ]
                        ];
                    }

                    $existsPersonaArea = PersonaArea::where('id_area', $area['id_area'])
                        ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                        ->exists();

                    if ($existsPersonaArea) {
                        return [
                            'status_code' => 422,
                            'content' => [
                                'status' => 'error',
                                'message' => ' Ya existe un Responsable asignado a esta área.',
                            ]
                        ];
                    }
                    PersonaArea::create([
                        'id_persona' => $persona->id,
                        'id_area' => $area['id_area'],
                    ]);

                    $areaNiveles = AreaNivel::where('id_area', $area['id_area'])->get();

                    if ($areaNiveles->isEmpty()) {
                        return [
                            'status_code' => 422,
                            'content' => [
                                'message' => 'Error: No se encontraron niveles asociados a esta área.',
                                'errors' => 'Área no tiene niveles asociados.'
                            ]

                        ];
                    }

                    foreach ($areaNiveles as $areaNivel) {
                        $exists = Asignacion::where('id_area_nivel', $areaNivel->id)
                            ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                            ->where('id_persona', '!=', $persona->id)
                            ->exists();

                        if ($exists) {
                            return [
                                'status_code' => 422,
                                'content' => [
                                    'status' => 'error',
                                    'message' => 'Error: Ya existe un Responsable asignado a esta área',
                                    'errors' => [
                                        'asignaciones' => [
                                            "Área {$areaNivel->id_area} ya tiene un Responsable asignado."
                                        ]
                                    ]
                                ]
                            ];
                        }
                        Asignacion::create([
                            'id_persona' => $persona->id,
                            'id_area_nivel' => $areaNivel->id,
                        ]);
                    }

                }

                // Recargar relaciones
                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel', 'persona_areas.area']);

                return [
                    'status_code' => 200,
                    'content' => [
                        'status' => 'success',
                        'message' => 'Responsable actualizado correctamente.',

                    ],

                ];
            });

        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error interno al registrar responsable.',
                ]
            ];
        }
    }
    public function eliminarResponsableAcademico($id)
    {
        $exist = Persona::find($id);
        if (!$exist) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Responsable no encontrado.',
                ]
            ];
        }
        $persona = Persona::with('user', 'asignacions')->findOrFail($id);

        return DB::transaction(function () use ($persona) {
            $persona->asignacions()->delete();
            $persona->persona_areas()->delete();
            $persona->user()->delete();
            $persona->delete();
            $invitacion = $this->invitacionRepo->findByEmail($persona->email);
            $this->invitacionRepo->delete($invitacion);
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Responsable eliminado correctamente.',
                ],

            ];
        });
    }
}
