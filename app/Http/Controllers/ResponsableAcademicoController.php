<?php

namespace App\Http\Controllers;

use App\Models\ResponsableAcademico;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResponsableAcademicoController extends Controller
{
    /**
     * Muestra la lista de todos los responsables académicos.
     */
    public function index(): JsonResponse
    {
        $responsables = ResponsableAcademico::all();
        return response()->json($responsables);
    }

    /**
     * Registra un nuevo responsable académico.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'correo' => 'required|email|unique:responsable_academicos,correo',
            'telefono' => 'required|string|max:20',
            'area' => 'required|string|unique:responsable_academicos,area',
        ]);

        $responsable = ResponsableAcademico::create($validated);

        return response()->json([
            'message' => 'Responsable académico registrado correctamente.',
            'data' => $responsable
        ], 201);
    }
}