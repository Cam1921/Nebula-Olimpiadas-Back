<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCompetidoresRequest;
use App\Services\ListaCompetidoresService;
use Illuminate\Http\Request;

class ListaCompetidoresController extends Controller
{
    protected $competidoresService;

    public function __construct(ListaCompetidoresService $competidoresService)
    {
        $this->competidoresService = $competidoresService;
    }
    public function import(ImportCompetidoresRequest $request)
    {
        $file = $request->file('archivo');
        $resultado = $this->competidoresService->importarCsv($file);


        if ($resultado['success']) {
            $data = [
                'importados' => $resultado['importados'],
                'areas' => $resultado['areas'],
                'niveles' => $resultado['niveles']
            ];

            return response()->json([
                'message' => 'Competidores importados con éxito',
                'data' => $data
            ], 201);
        }

        return response()->json([
            'error' => 'Error al importar competidores',
            'detalle' => $resultado['error']
        ], 500);
    }
    /* public function index(Request $request)
    {
        // filtros opcionales
        $idArea = $request->input('area_id');
        $idNivel = $request->input('nivel_id');

        // cantidad por página (default = 10)
        $perPage = $request->input('per_page', 10);

        $competidores = $this->competidoresService
            ->listarCompetidores([
                'area_id' => $idArea,
                'nivel_id' => $idNivel
            ], $perPage);

        return response()->json($competidores);
    } */
}
