<?php
// app/Http/Controllers/ResponsableAcademicoController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreResponsableAcademicoRequest;
use App\Models\AreaNivel;
use App\Models\Asignacion;
use App\Models\Invitacion;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use App\Repositories\InvitacionRepository;
use App\Services\EvaluadoresService;
use App\Services\PersonaService;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ResponsableAcademicoController extends Controller
{

    protected $invitacionRepo;

    protected $evaluadorService;
    protected $personaService;

    public function __construct(EvaluadoresService $evaluadoresService, PersonaService $personaService, InvitacionRepository $invitacionRepo)
    {
        $this->evaluadorService = $evaluadoresService;
        $this->personaService = $personaService;
        $this->invitacionRepo = $invitacionRepo;
    }
    public function index(): JsonResponse
    {
        $evaluadores = Persona::with([
            'user:id,email',
            'asignacions.area_nivel.area:id,nombre_area',
            'asignacions.area_nivel.nivel:id,nombre_nivel'
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
                    'asignaciones' => $persona->asignacions
                        ->pluck('area_nivel.area.nombre_area') // Trae solo el nombre del área
                        ->unique()                             // Elimina duplicados
                        ->values(),
                    'fecha_registro' => optional($persona->created_at)->format('Y-m-d'),
                ];
            });

        return response()->json($evaluadores);
    }

    /**
     * Crear nuevo evaluador
     */
    public function store(StoreResponsableAcademicoRequest $request): JsonResponse
    {
        // ✅ Los datos ya están validados
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            try {
                // Obtener rol evaluador
                $rolResponsable = Rol::where('nombre', 'responsable')->firstOrFail();

                // Crear usuario
                $user = User::create([
                    'name' => "{$validated['nombre']} {$validated['apellidos']}",
                    'email' => $validated['email'],
                    'password' => Hash::make(Str::random(16)), // 🔹 aquí
                ]);

                // Crear persona
                $persona = Persona::create([
                    'nombres' => $validated['nombre'],
                    'apellidos' => $validated['apellidos'],
                    'ci' => $validated['ci'],
                    'telefono' => $validated['telefono'],
                    'email' => $validated['email'],
                    'id_usuario' => $user->id,
                ]);

                // Asignar rol
                $persona->rols()->attach($rolResponsable->id);

                // Crear asignaciones para cada área y todos sus niveles
                foreach ($validated['asignaciones'] as $a) {
                    $areaNiveles = AreaNivel::where('id_area', $a['id_area'])->get();

                    if ($areaNiveles->isEmpty()) {
                        return response()->json([
                            'message' => 'Error: No se encontraron niveles asociados a esta área.',
                            'errors' => ['asignaciones' => ["Área {$a['id_area']} no tiene niveles asociados."]]
                        ], 422);
                    }

                    foreach ($areaNiveles as $areaNivel) {

                        $exists = Asignacion::where('id_area_nivel', $areaNivel->id)
                            ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                            ->exists();
                        // Verificar duplicados

                        if ($exists) {
                            return response()->json([
                                'message' => 'Error: Ya existe un Responsable asignado a esta área',
                                'errors' => [
                                    'asignaciones' => [
                                        "Área {$areaNivel->id_area}  ya tienen un Responsable asignado."
                                    ]
                                ]
                            ], 422);
                        }

                        // Crear asignación
                        Asignacion::create([
                            'id_persona' => $persona->id,
                            'id_area_nivel' => $areaNivel->id,
                        ]);
                    }
                }
                $resCorreo = $this->personaService->enviarCorreoCreacionPassword($persona->id);
                // Cargar relaciones para la respuesta
                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel']);

                return response()->json([
                    'message' => 'reponsable registrado correctamente.',
                    'data' => $persona,
                ], 201);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error interno al registrar evaluador.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        });
    }
    /**
     * Actualizar datos de un evaluador
     */
    public function update(Request $request, $id): JsonResponse
    {
        $persona = Persona::with('user', 'rols')->findOrFail($id);

        try {
            $request->validate([
                'nombre' => 'sometimes|string|min:2',
                'apellidos' => 'sometimes|string|min:2',
                'ci' => "sometimes|numeric|unique:persona,ci,{$id},id",
                'telefono' => "sometimes|string|size:8|regex:/^[67]\d{7}$/|unique:persona,telefono,{$id},id",
                'email' => "sometimes|email|unique:persona,email,{$id},id",
                'asignaciones' => 'sometimes|array|min:1',
                'asignaciones.*.id_area' => 'sometimes|integer|exists:area,id',
            ], [
                // Mensajes personalizados
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
                'asignaciones.array' => 'Las asignaciones deben enviarse en un arreglo.',
                'asignaciones.min' => 'Debe asignar al menos un área.',
                'asignaciones.*.id_area.integer' => 'El ID del área debe ser un número entero.',
                'asignaciones.*.id_area.exists' => 'El área seleccionada no existe.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            // ✅ TRANSACCIÓN SEGURA
            return DB::transaction(function () use ($request, $persona) {

                // 1️⃣ Actualizar solo los campos que vengan en el request
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

                // 2️⃣ Actualizar Usuario si se cambió el email o CI
                if ($persona->user) {
                    $userData = [];
                    if ($request->filled('email')) {
                        $userData['email'] = $request->email;
                    }
                    if ($request->filled('ci')) {
                        $userData['password'] = Hash::make($request->ci);
                    }
                    if (!empty($userData)) {
                        $persona->user->update($userData);
                    }
                    if ($request->filled('email') && $persona->email !== $request->email) {
                        $invitacion = Invitacion::where('email', $persona->email)->first();

                        if ($invitacion) {
                            $invitacion->update(['email' => $request->email]);
                        }
                    }
                }

                // 3️⃣ Actualizar asignaciones solo si se enviaron
                if ($request->has('asignaciones')) {
                    // Eliminar asignaciones previas
                    $persona->asignacions()->delete();

                    foreach ($request->asignaciones as $a) {
                        $areaNiveles = AreaNivel::where('id_area', $a['id_area'])->get();

                        if ($areaNiveles->isEmpty()) {
                            return response()->json([
                                'message' => 'Error: No se encontraron niveles asociados a esta área.',
                                'errors' => ['asignaciones' => ["Área {$a['id_area']} no tiene niveles asociados."]]
                            ], 422);
                        }

                        foreach ($areaNiveles as $areaNivel) {
                            $exists = Asignacion::where('id_area_nivel', $areaNivel->id)
                                ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'responsable'))
                                ->where('id_persona', '!=', $persona->id)
                                ->exists();

                            if ($exists) {
                                return response()->json([
                                    'message' => 'Error: Ya existe un Responsable asignado a esta área',
                                    'errors' => [
                                        'asignaciones' => [
                                            "Área {$areaNivel->id_area} ya tiene un Responsable asignado."
                                        ]
                                    ]
                                ], 422);
                            }

                            // Crear nueva asignación
                            Asignacion::create([
                                'id_persona' => $persona->id,
                                'id_area_nivel' => $areaNivel->id,
                            ]);
                        }
                    }
                }

                // Recargar relaciones
                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel']);

                return response()->json([
                    'message' => 'Responsable actualizado correctamente.',
                    'data' => $persona,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }






    /**
     * Eliminar un evaluador
     */
    public function destroy($id): JsonResponse
    {
        $persona = Persona::with('user', 'asignacions')->findOrFail($id);

        return DB::transaction(function () use ($persona) {
            $persona->asignacions()->delete();
            $persona->user()->delete();
            $persona->delete();
            $invitacion = $this->invitacionRepo->findByEmail($persona->email);
            $this->invitacionRepo->delete($invitacion);
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