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

        $responsables = ResponsableAcademico::all();


        $areasDisponibles = ['Matemáticas', 'Física', 'Química', 'Biología', 'Computación'];

       
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

        public function areasDisponibles(): JsonResponse
{
    // 1. Catálogo completo de áreas
    $catalogo = ['Matemáticas', 'Física', 'Química', 'Biología', 'Computación'];

    // 2. Áreas ya asignadas (de la base de datos)
    $areasAsignadas = ResponsableAcademico::pluck('area')->toArray();

    // 3. Áreas disponibles = catálogo - asignadas
    $disponibles = array_values(array_diff($catalogo, $areasAsignadas));

    return response()->json([
        'areas_disponibles' => $disponibles
    ]);
}
        
}
}