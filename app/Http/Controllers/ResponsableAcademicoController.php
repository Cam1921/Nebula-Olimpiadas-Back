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
}