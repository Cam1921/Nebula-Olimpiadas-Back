<?php

namespace App\Http\Controllers;

use App\Models\Evaluador;
use App\Http\Requests\StoreEvaluadorRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EvaluadorController extends Controller
{
    public function index(): JsonResponse
    {
        $evaluadores = Evaluador::all()->map(function ($e) {
            return [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'apellidos' => $e->apellidos,
                'correo' => $e->correo,
                'telefono' => '+591 ' . $e->telefono,
                'ci' => $e->ci,
                'area' => $e->area,
                'nivel' => $e->nivel,
                'fecha' => $e->fecha_registro->format('Y-m-d'),
            ];
        });
        return response()->json($evaluadores);
    }

    public function store(StoreEvaluadorRequest $request): JsonResponse
    {
        $data = $request->validated();

        // ✅ Validación manual para evitar error 500 si la combinación area+nivel ya existe
        $exists = Evaluador::where('area', $data['area'])
                           ->where('nivel', $data['nivel'])
                           ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Error: Ya existe un evaluador asignado a esta área y nivel.',
                'errors' => [
                    'area' => ['Ya existe un evaluador para esta combinación de área y nivel.']
                ]
            ], 422);
        }

        $evaluador = Evaluador::create([
            'nombre' => $data['nombre'],
            'apellidos' => $data['apellidos'],
            'correo' => $data['correo'],
            'telefono' => $data['telefono'],
            'ci' => $data['ci'],
            'area' => $data['area'],
            'nivel' => $data['nivel'],
            'fecha_registro' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Evaluador registrado correctamente.',
            'data' => [
                'id' => $evaluador->id,
                'nombre' => $evaluador->nombre,
                'apellidos' => $evaluador->apellidos,
                'correo' => $evaluador->correo,
                'telefono' => '+591 ' . $evaluador->telefono,
                'ci' => $evaluador->ci,
                'area' => $evaluador->area,
                'nivel' => $evaluador->nivel,
                'fecha' => $evaluador->fecha_registro->format('Y-m-d'),
            ]
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|min:3',
            'apellidos' => 'required|string|min:3',
            'correo' => "required|email|unique:evaluadores,correo,$id",
            'telefono' => "required|string|size:8|regex:/^[67]\d{7}$/|unique:evaluadores,telefono,$id",
            'ci' => "required|numeric|digits_between:6,10|unique:evaluadores,ci,$id",
            'area' => 'required|string|max:255',
            'nivel' => 'required|string|max:255',
        ]);

        $evaluador = Evaluador::findOrFail($id);

        // ✅ Validación manual para evitar error 500 si la combinación area+nivel ya existe (y no es la misma fila)
        $exists = Evaluador::where('area', $request->area)
                           ->where('nivel', $request->nivel)
                           ->where('id', '!=', $id) // 👈 Excluye el registro actual
                           ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Error: Ya existe un evaluador asignado a esta área y nivel.',
                'errors' => [
                    'area' => ['Ya existe un evaluador para esta combinación de área y nivel.']
                ]
            ], 422);
        }

        $evaluador->update([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'ci' => $request->ci,
            'area' => $request->area,
            'nivel' => $request->nivel,
            'fecha_registro' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Evaluador actualizado correctamente.',
            'data' => [
                'id' => $evaluador->id,
                'nombre' => $evaluador->nombre,
                'apellidos' => $evaluador->apellidos,
                'correo' => $evaluador->correo,
                'telefono' => '+591 ' . $evaluador->telefono,
                'ci' => $evaluador->ci,
                'area' => $evaluador->area,
                'nivel' => $evaluador->nivel,
                'fecha' => $evaluador->fecha_registro->format('Y-m-d'),
            ]
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $evaluador = Evaluador::findOrFail($id);
        $evaluador->delete();

        return response()->json([
            'message' => 'Evaluador eliminado correctamente.'
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $field = $request->query('field');
        $value = $request->query('value');
        $excludeId = $request->query('excludeId'); // ← Nueva línea

        if (!in_array($field, ['correo', 'telefono', 'ci'])) {
            return response()->json(['error' => 'Campo no permitido'], 400);
        }

        $query = Evaluador::where($field, $value);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId); // ← Nueva línea
        }
        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
    }
}