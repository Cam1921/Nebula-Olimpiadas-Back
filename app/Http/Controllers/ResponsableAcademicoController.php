<?php
// app/Http/Controllers/ResponsableAcademicoController.php
namespace App\Http\Controllers;

use App\Models\AreaNivel;
use App\Models\Asignacion;
use App\Models\Persona;
use App\Models\ResponsableAcademico;
use App\Http\Requests\StoreResponsableAcademicoRequest;
use App\Models\Rol;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResponsableAcademicoController extends Controller
{
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
    public function store(Request $request): JsonResponse
    {
        try {
            // ✅ VALIDACIÓN DE CAMPOS
            $validated = $request->validate([
                'nombre' => 'required|string|min:2',
                'apellidos' => 'required|string|min:2',
                'ci' => 'required|numeric|unique:persona,ci',
                'telefono' => 'required|string|size:8|regex:/^[67]\d{7}$/|unique:persona,telefono',
                'email' => 'required|email|unique:users,email',
                'asignaciones' => 'required|array|min:1',
                'asignaciones.*.id_area' => 'required|integer|exists:area,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ❌ SI FALLA LA VALIDACIÓN
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($validated) {
            try {
                // Obtener rol evaluador
                $rolResponsable = Rol::where('nombre', 'responsable')->firstOrFail();

                // Crear usuario
                $user = User::create([
                    'name' => "{$validated['nombre']} {$validated['apellidos']}",
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['ci']),
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
            // ✅ VALIDACIÓN DE CAMPOS
            $request->validate([
                'nombre' => 'required|string|min:2',
                'apellidos' => 'required|string|min:2',
                'ci' => "required|numeric|unique:persona,ci,{$id}",
                'telefono' => "required|string|size:8|regex:/^[67]\d{7}$/|unique:persona,telefono,{$id}",
                'email' => "required|email|unique:users,email,{$persona->user->id}",
                'asignaciones' => 'required|array|min:1',
                'asignaciones.*.id_area' => 'required|integer|exists:area,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ❌ SI FALLA LA VALIDACIÓN
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            // ✅ SI TODO ES VÁLIDO, INICIA TRANSACCIÓN
            return DB::transaction(function () use ($request, $persona) {

                // 1️⃣ Actualizar Persona
                $persona->update([
                    'nombres' => $request->nombre,
                    'apellidos' => $request->apellidos,
                    'ci' => $request->ci,
                    'telefono' => $request->telefono,
                    'email' => $request->email,
                ]);

                // 2️⃣ Actualizar Usuario
                if ($persona->user) {
                    $persona->user->update([
                        'email' => $request->email,
                        'password' => Hash::make($request->ci),
                    ]);
                }

                // 3️⃣ Eliminar asignaciones previas
                $persona->asignacions()->delete();

                // 4️⃣ Crear nuevas asignaciones
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

                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel']);

                return response()->json([
                    'message' => 'responsable actualizado correctamente.',
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