<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PersonaController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $persona = $user->personas()->with(['rols', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel'])->first();

        if (!$persona) {
            return response()->json(['message' => 'No se encontró la persona asociada.'], 404);
        }
        $roles = $persona->rols->pluck('nombre')->toArray();
        $data = [
            'persona' => [
                'id' => $persona->id,
                'nombre' => $persona->nombres,
                'apellidos' => $persona->apellidos,
                'telefono' => $persona->telefono,
                'ci' => $persona->ci,
                'correo' => $user->email,
            ],
            'fecha_registro' => optional($persona->created_at)->format('Y-m-d'),
        ];


        if (in_array('responsable', $roles)) {
            $data['asignaciones'] = $persona->asignacions->map(fn($a) => [
                'area' => optional($a->area_nivel->area)->nombre_area,
            ]);
        } elseif (in_array('evaluador', $roles)) {
            $data['asignaciones'] = $persona->asignacions->map(fn($a) => [
                'area' => optional($a->area_nivel->area)->nombre_area,
                'nivel' => optional($a->area_nivel->nivel)->nombre_nivel,
            ]);
        } else {
            $data['asignaciones'] = [];
        }

        return response()->json($data);
    }
    public function update(Request $request)
    {
        $user = $request->user();
        $persona = $user->personas()->with(['rols', 'asignacions.area_nivel.area', 'asignacions.area_nivel.nivel'])->first();

        if (!$persona) {
            return response()->json(['message' => 'No se encontró la persona asociada.'], 404);
        }


        $request->validate([
            'nombre' => 'sometimes|string|min:2',
            'apellidos' => 'sometimes|string|min:2',
            'telefono' => 'sometimes|string|size:8|regex:/^[67]\d{7}$/',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);


        if ($request->filled('nombre')) {
            $persona->nombres = $request->nombre;
        }

        if ($request->filled('apellidos')) {
            $persona->apellidos = $request->apellidos;
        }

        if ($request->filled('telefono')) {
            $persona->telefono = $request->telefono;
        }

        $persona->save();


        if ($request->filled('password')) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        }


        $roles = $persona->rols->pluck('nombre')->toArray();


        $data = [
            'message' => 'Datos actualizados correctamente',
            'persona' => [
                'id' => $persona->id,
                'nombre' => $persona->nombres,
                'apellidos' => $persona->apellidos,
                'telefono' => $persona->telefono,
                'ci' => $persona->ci,
                'correo' => $user->email,
            ],
            'fecha_registro' => optional($persona->created_at)->format('Y-m-d'),
        ];


        if (in_array('responsable', $roles)) {
            $data['asignaciones'] = $persona->asignacions->map(fn($a) => [
                'area' => optional($a->area_nivel->area)->nombre_area,
            ]);
        } elseif (in_array('evaluador', $roles)) {
            $data['asignaciones'] = $persona->asignacions->map(fn($a) => [
                'area' => optional($a->area_nivel->area)->nombre_area,
                'nivel' => optional($a->area_nivel->nivel)->nombre_nivel,
            ]);
        } else {
            $data['asignaciones'] = [];
        }

        return response()->json($data);
    }

}
