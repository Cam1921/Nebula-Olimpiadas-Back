<?php

namespace App\Http\Controllers;

use App\Models\AreaNivel;
use App\Models\Persona;
use App\Models\Asignacion;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class EvaluadorController extends Controller
{
    /**
     * Listar todos los evaluadores con sus asignaciones
     */
    public function index(): JsonResponse
    {
        $evaluadores = Persona::with([
            'user:id,email',
            'asignacions.area_nivel.area:id,nombre_area',
            'asignacions.area_nivel.nivel:id,nombre_nivel'
        ])
            ->whereHas('rols', fn($q) => $q->where('nombre', 'evaluador'))
            ->get()
            ->map(function ($persona) {
                return [
                    'id' => $persona->id,
                    'nombre' => $persona->nombres,
                    'apellidos' => $persona->apellidos,
                    'ci' => $persona->ci,
                    'correo' => $persona->user->email ?? null,
                    'telefono' => '+591 ' . $persona->telefono,
                    'asignaciones' => $persona->asignacions->map(fn($a) => [
                        'area' => optional($a->area_nivel->area)->nombre_area,
                        'nivel' => optional($a->area_nivel->nivel)->nombre_nivel,
                    ]),
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
            $request->validate([
                'nombre' => 'required|string|min:2',
                'apellidos' => 'required|string|min:2',
                'ci' => 'required|numeric|unique:persona,ci',
                'telefono' => 'required|string|size:8|regex:/^[67]\d{7}$/|unique:persona,telefono',
                'email' => 'required|email|unique:users,email',
                'asignaciones' => 'required|array|min:1',
                'asignaciones.*.id_area' => 'required|integer|exists:area,id',
                'asignaciones.*.id_nivel' => 'nullable|integer|exists:nivel,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ❌ SI FALLA LA VALIDACIÓN
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $rolEvaluador = Rol::where('nombre', 'evaluador')->firstOrFail();

            $user = User::create([
                'name' => "{$request->nombre} {$request->apellidos}",
                'email' => $request->email,
                'password' => Hash::make($request->ci),

            ]);
            // Crear persona
            $persona = Persona::create([
                'nombres' => $request->nombre,
                'apellidos' => $request->apellidos,
                'ci' => $request->ci,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'id_usuario' => $user->id,
            ]);

            $persona->rols()->attach($rolEvaluador->id);

            // Crear usuario (contraseña = CI)


            // Validar duplicados de asignación
            foreach ($request->asignaciones as $a) {
                // Buscar el área_nivel correspondiente
                $areaNivel = AreaNivel::where('id_area', $a['id_area'])
                    ->where('id_nivel', $a['id_nivel'])
                    ->first();

                if (!$areaNivel) {
                    return response()->json([
                        'message' => 'Error: No se encontró una combinación válida de área y nivel.',
                        'errors' => [
                            'asignaciones' => ['Área o nivel no existen en la tabla area_nivel.']
                        ]
                    ], 422);
                }

                // Verificar si ya hay un evaluador asignado a ese área_nivel
                $exists = Asignacion::where('id_area_nivel', $areaNivel->id)
                    ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'evaluador'))
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'Error: Ya existe un evaluador asignado a esta área y nivel.',
                        'errors' => [
                            'asignaciones' => ['Duplicado de área y nivel.']
                        ]
                    ], 422);
                }

                // Crear la asignación nueva
                Asignacion::create([
                    'id_persona' => $persona->id,
                    'id_area_nivel' => $areaNivel->id,
                ]);
            }

            return response()->json([
                'message' => 'Evaluador registrado correctamente.',
                'data' => $persona->load([
                    'user',
                    'asignacions.area_nivel.area',
                    'asignacions.area_nivel.nivel',
                ]),
            ], 201);
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
                'asignaciones.*.id_nivel' => 'nullable|integer|exists:nivel,id',
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
                    $areaNivel = AreaNivel::where('id_area', $a['id_area'])
                        ->where('id_nivel', $a['id_nivel'])
                        ->first();

                    if (!$areaNivel) {
                        return response()->json([
                            'message' => 'Error: No se encontró una combinación válida de área y nivel.',
                            'errors' => [
                                'asignaciones' => ['Área o nivel no existen en la tabla area_nivel.']
                            ]
                        ], 422);
                    }

                    // Verificar si ya hay un evaluador asignado a ese área_nivel
                    $exists = Asignacion::where('id_area_nivel', $areaNivel->id)
                        ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'evaluador'))
                        ->exists();

                    if ($exists) {
                        return response()->json([
                            'message' => 'Error: Ya existe un evaluador asignado a esta área y nivel.',
                            'errors' => [
                                'asignaciones' => ['Duplicado de área y nivel.']
                            ]
                        ], 422);
                    }

                    $persona->asignacions()->create([
                        'id_area_nivel' => $areaNivel->id,
                    ]);
                }

                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel']);

                return response()->json([
                    'message' => 'Evaluador actualizado correctamente.',
                    'data' => $persona,
                ]);
            });
        } catch (\Exception $e) {
            // ❌ ERRORES GENERALES (por ejemplo, asignación duplicada)
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
