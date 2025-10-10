<?php

namespace App\Http\Controllers;


use App\Exports\CompetidoresExport;
use App\Services\ListarCompetidoresService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;


class ListarInscritosCotroller extends Controller
{
    private ListarCompetidoresService $competidorService;

    public function __construct(ListarCompetidoresService $competidorService)
    {
        $this->competidorService = $competidorService;
    }

    /**
     * GET /api/competidores
     * Filtros: ?area_id=1&nivel_id=2&page=1&per_page=10
     */
    public function listar(Request $request)
    {
        try {
            $areaId = $request->input('area_id');
            $nivelId = $request->input('nivel_id');
            $busqueda = $request->input('busqueda');
            $page = max((int) $request->input('page', 1), 1);
            $perPage = max((int) $request->input('per_page', 10), 1);

            // Validaciones
            if ($areaId && !is_numeric($areaId)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['El parámetro area_id debe ser numérico.']
                ], 400);
            }

            if ($nivelId && !is_numeric($nivelId)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['El parámetro nivel_id debe ser numérico.']
                ], 400);
            }

            $paginator = $this->competidorService->listarCompetidores($areaId, $nivelId, $busqueda, $page, $perPage);

            if ($paginator->total() === 0) {
                return response()->json([
                    'success' => false,
                    'errors' => ['No se encontraron competidores para los filtros seleccionados.']
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $paginator->items(),
                'meta' => [
                    'pagination' => [
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error listar competidores: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => ['Ocurrió un error inesperado al procesar la solicitud.']
            ], 500);
        }
    }
    public function exportar(Request $request)
    {
        $areaId = $request->query('area_id');
        $nivelId = $request->query('nivel_id');
        $busqueda = $request->query('busqueda');

        return Excel::download(
            new CompetidoresExport($areaId, $nivelId, $busqueda),
            'competidores.xlsx'
        );
    }
}
