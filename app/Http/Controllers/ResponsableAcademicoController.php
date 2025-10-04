<?php

namespace App\Http\Controllers;

use App\Models\ResponsableAcademico;
use App\Http\Requests\StoreResponsableAcademicoRequest;
use Illuminate\Http\JsonResponse;

class ResponsableAcademicoController extends Controller
{
    public function store(StoreResponsableAcademicoRequest $request): JsonResponse
    {
        $responsable = ResponsableAcademico::create($request->validated());

        return response()->json([
            'message' => 'Responsable académico registrado correctamente.',
            'data' => $responsable
        ], 201);
    }

     public function index(): JsonResponse
    {
        // 1. Obtener todos los responsables
        $responsables = ResponsableAcademico::all();

        // 2. Definir catálogo de áreas disponibles
        $areasDisponibles = ['Matemáticas', 'Física', 'Química', 'Biología', 'Computación'];

        // 3. Calcular KPIs
        $total = $responsables->count();
        $cubiertas = $responsables->pluck('area')->unique()->count();
        $disponibles = count($areasDisponibles);

        return response()->json([
            'data' => $responsables,
            'kpi' => [
                'total' => $total,
                'cubiertas' => $cubiertas,
                'disponibles' => $disponibles,
                 ]
        ]);
        }
}