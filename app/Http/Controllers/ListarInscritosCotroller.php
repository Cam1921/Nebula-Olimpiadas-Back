<?php

namespace App\Http\Controllers;


use App\Exports\CompetidoresExport;
use App\Services\ListarCompetidoresService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 *Controlador para listar competidores inscritos.
 */
class ListarInscritosCotroller extends Controller
{
    protected $competidorService;

    /**
     * Constructor de la clase.
     * @param ListarCompetidoresService $competidorService
     */
    public function __construct(ListarCompetidoresService $competidorService)
    {
        $this->competidorService = $competidorService;
    }



    /**
     * Lista competidores con filtros y paginación.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listar(Request $request)
    {
        $areaId = $request->input('area_id');
        $nivelId = $request->input('nivel_id');
        $busqueda = $request->input('busqueda');
        $page = max((int) $request->input('page', 1), 1);
        $perPage = max((int) $request->input('per_page', 10), 1);
        $res = $this->competidorService->listarCompetidores($areaId, $nivelId, $busqueda, $page, $perPage);
        return response()->json($res['content'], $res['status_code']);
    }


    /**
     * Exporta la lista de competidores en excel
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
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
