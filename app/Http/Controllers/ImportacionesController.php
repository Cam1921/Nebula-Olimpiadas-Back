<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCompetidoresRequest;
use App\Services\ListaCompetidoresService;
use Illuminate\Http\Request;

class ImportacionesController extends Controller
{
    protected $competidoresService;

    public function __construct(ListaCompetidoresService $competidoresService)
    {
        $this->competidoresService = $competidoresService;
    }

    /**
     * PREVIEW: Valida CSV y devuelve filas válidas, errores y advertencias
     */
    public function preview(ImportCompetidoresRequest $request)
    {
        $file = $request->file('archivo');
        $resultado = $this->competidoresService->previewCsv($file);

        return response()->json(
            $resultado,
            $resultado['code'] ?? 200
        );
    }

    /**
     * CONFIRMAR: Guarda en BD las filas válidas
     */
    public function confirmar(Request $request)
    {
        $filasValidas = $request->input('filas_validas', []);
        $resultado = $this->competidoresService->confirmarCsv($filasValidas);

        return response()->json(
            $resultado,
            $resultado['code'] ?? 201
        );
    }
}
