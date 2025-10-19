<?php
// app/Http/Controllers/ResponsableAcademicoController.php
namespace App\Http\Controllers;

use App\Models\ResponsableAcademico;
use App\Http\Requests\StoreResponsableAcademicoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResponsableAcademicoController extends Controller
{
    public function index(): JsonResponse
    {
        $responsables = ResponsableAcademico::all()->map(function ($r) {
            return [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'apellidos' => $r->apellidos,
                'correo' => $r->correo,
                'telefono' => '+591 ' . $r->telefono,
                'ci' => $r->ci,
                'area' => $r->area,
                'fecha' => $r->fecha_registro->format('Y-m-d'),
            ];
        });

        return response()->json($responsables);
    }

    public function store(StoreResponsableAcademicoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $responsable = ResponsableAcademico::create([
            'nombre' => $data['nombre'],
            'apellidos' => $data['apellidos'],
            'correo' => $data['correo'],
            'telefono' => $data['telefono'],
            'ci' => $data['ci'],
            'area' => $data['area'],
            'fecha_registro' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Responsable académico registrado correctamente.',
            'data' => [
                'id' => $responsable->id,
                'nombre' => $responsable->nombre,
                'apellidos' => $responsable->apellidos,
                'correo' => $responsable->correo,
                'telefono' => '+591 ' . $responsable->telefono,
                'ci' => $responsable->ci,
                'area' => $responsable->area,
                'fecha' => $responsable->fecha_registro->format('Y-m-d'),
            ]
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|min:3',
            'apellidos' => 'required|string|min:3',
            'correo' => "required|email|max:70|unique:responsable_academicos,correo,$id",
            'telefono' => "required|string|size:8|regex:/^[67]\d{7}$/|unique:responsable_academicos,telefono,$id",
            'ci' => "required|numeric|digits_between:6,10|unique:responsable_academicos,ci,$id",
            'area' => "required|string|max:255|unique:responsable_academicos,area,$id",
        ]);

        $responsable = ResponsableAcademico::findOrFail($id);
        $responsable->update([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'ci' => $request->ci,
            'area' => $request->area,
            'fecha_registro' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Responsable académico actualizado correctamente.',
            'data' => [
                'id' => $responsable->id,
                'nombre' => $responsable->nombre,
                'apellidos' => $responsable->apellidos,
                'correo' => $responsable->correo,
                'telefono' => '+591 ' . $responsable->telefono,
                'ci' => $responsable->ci,
                'area' => $responsable->area,
                'fecha' => $responsable->fecha_registro->format('Y-m-d'),
            ]
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $responsable = ResponsableAcademico::findOrFail($id);
        $responsable->delete();

        return response()->json([
            'message' => 'Responsable académico eliminado correctamente.'
        ]);
    }

    // Endpoint para verificar existencia (con soporte para excludeId)
    public function check(Request $request): JsonResponse
    {
        $field = $request->query('field');
        $value = $request->query('value');
        $excludeId = $request->query('excludeId'); // ← Nueva línea

        if (!in_array($field, ['correo', 'telefono', 'ci'])) {
            return response()->json(['error' => 'Campo no permitido'], 400);
        }

        $query = ResponsableAcademico::where($field, $value);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId); // ← Nueva línea
        }
        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
    }
}
