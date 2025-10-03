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

        // Manejo de error
        if ($resultado['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $resultado['message'] ?? 'Error desconocido',
                'importados' => $resultado['importados'] ?? [],
                'errores' => $resultado['errores'] ?? []
            ], 400);
        }

        // Éxito total o parcial
        return response()->json([
            'status' => 'ok',
            'import_id' => (string) \Illuminate\Support\Str::uuid(),
            'insertados' => $resultado['insertados'],
            'importados' => $resultado['importados'] ?? [],
            'errores' => $resultado['errores'] ?? []
        ], empty($resultado['errores'] ?? []) ? 201 : 207); // 207 = Multi-Status
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
