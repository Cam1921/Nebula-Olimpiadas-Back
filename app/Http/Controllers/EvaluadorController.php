<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluadorRequest;
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
    public function store(StoreEvaluadorRequest $request): JsonResponse
    {
        $datos = $request->validated(); // ✅ Guardamos los datos validados en un array

        return DB::transaction(function () use ($datos) {
            $rolEvaluador = Rol::where('nombre', 'evaluador')->firstOrFail();

            // Crear usuario
            $user = User::create([
                'name' => "{$datos['nombre']} {$datos['apellidos']}",
                'email' => $datos['email'],
                'password' => Hash::make($datos['ci']),
            ]);

            // Crear persona
            $persona = Persona::create([
                'nombres' => $datos['nombre'],
                'apellidos' => $datos['apellidos'],
                'ci' => $datos['ci'],
                'telefono' => $datos['telefono'],
                'email' => $datos['email'],
                'id_usuario' => $user->id,
            ]);

            // Asignar rol de evaluador
            $persona->rols()->attach($rolEvaluador->id);

            // Crear asignaciones
            foreach ($datos['asignaciones'] as $a) {
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
            // ✅ Laravel lanza automáticamente ValidationException si no cumple
            $validated = $request->validate([
                'nombre' => 'sometimes|string|min:2',
                'apellidos' => 'sometimes|string|min:2',
                'ci' => "sometimes|numeric|unique:persona,ci,{$id},id",
                'telefono' => "sometimes|string|size:8|regex:/^[67]\d{7}$/|unique:persona,telefono,{$id},id",
                'email' => "sometimes|email|unique:persona,email,{$id},id",
                'asignaciones' => 'sometimes|array',
                'asignaciones.*.id_area' => 'required_with:asignaciones|integer|exists:area,id',
                'asignaciones.*.id_nivel' => 'required_with:asignaciones|integer|exists:nivel,id',
            ], [
                // 📋 Mensajes personalizados
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

                'asignaciones.array' => 'Las asignaciones deben enviarse en formato de arreglo.',

                'asignaciones.*.id_area.required_with' => 'Debe indicar el área en cada asignación.',
                'asignaciones.*.id_area.integer' => 'El ID del área debe ser un número válido.',
                'asignaciones.*.id_area.exists' => 'El área seleccionada no existe.',

                'asignaciones.*.id_nivel.required_with' => 'Debe indicar el nivel en cada asignación.',
                'asignaciones.*.id_nivel.integer' => 'El ID del nivel debe ser un número válido.',
                'asignaciones.*.id_nivel.exists' => 'El nivel seleccionado no existe.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
        try {
            return DB::transaction(function () use ($validated, $persona) {

                // 1️⃣ Actualizar Persona
                $persona->update([
                    'nombres' => $validated['nombre'] ?? $persona->nombres,
                    'apellidos' => $validated['apellidos'] ?? $persona->apellidos,
                    'ci' => $validated['ci'] ?? $persona->ci,
                    'telefono' => $validated['telefono'] ?? $persona->telefono,
                    'email' => $validated['email'] ?? $persona->email,
                ]);

                // 2️⃣ Actualizar Usuario (si existe)
                if ($persona->user && isset($validated['email'])) {
                    $persona->user->update([
                        'email' => $validated['email'],
                        'password' => Hash::make($persona->ci),
                    ]);
                }

                // 3️⃣ Actualizar asignaciones si fueron enviadas
                if (isset($validated['asignaciones'])) {
                    $persona->asignacions()->delete();

                    foreach ($validated['asignaciones'] as $a) {
                        $areaNivel = AreaNivel::where('id_area', $a['id_area'])
                            ->where('id_nivel', $a['id_nivel'])
                            ->first();

                        if (!$areaNivel) {
                            throw new \Exception('Área o nivel no existen en la tabla area_nivel.');
                        }

                        $exists = Asignacion::where('id_area_nivel', $areaNivel->id)
                            ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'evaluador'))
                            ->exists();

                        if ($exists) {
                            throw new \Exception('Ya existe un evaluador asignado a esta área y nivel.');
                        }

                        $persona->asignacions()->create([
                            'id_area_nivel' => $areaNivel->id,
                        ]);
                    }
                }

                $persona->load(['user', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel']);

                return response()->json([
                    'message' => 'Evaluador actualizado correctamente.',
                    'data' => $persona,
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
