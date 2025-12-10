<?php

namespace App\Http\Controllers;

use App\Exports\EvaluacionAuditoria;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AuditoriaaController extends Controller
{
    protected $evaluacionAuditoriaService;

    public function __construct(AuditoriaService $evaluacionAuditoriaService)
    {
        $this->evaluacionAuditoriaService = $evaluacionAuditoriaService;
    }

    public function index(Request $request)
    {
        $result = $this->evaluacionAuditoriaService->obtenerAuditoria([
            'rol' => $request->query('id_rol'),
            'area' => $request->query('id_area'),
            'nivel' => $request->query('id_nivel'),
            'fase' => $request->query('id_fase'),
            'fecha' => $request->query('fecha'),
            'accion' => $request->query('accion'),
            'perPage' => $request->query('per_page', 10)
        ]);

        return response()->json($result['content'], $result['status_code']);
    }

    public function getCertificadoLogs(Request $request)
    {
        $result = $this->evaluacionAuditoriaService->getCertificadoslogs([
            'area' => $request->query('id_area'),
            'nivel' => $request->query('id_nivel'),
        ]);
        return response()->json($result['content'], $result['status_code']);
    }
    public function storeCertificadoLogs(Request $request)
    {
        $result = $this->evaluacionAuditoriaService->generarCertificado($request);
        return response()->json($result['content'], $result['status_code']);
    }
    public function exportarLogsEvaluaciones(Request $request)
    {
        $res = $this->evaluacionAuditoriaService->obtenerAuditoria([
            'rol' => $request->query('id_rol'),
            'area' => $request->query('id_area'),
            'nivel' => $request->query('id_nivel'),
            'fase' => $request->query('id_fase'),
            'fecha' => $request->query('fecha'),
            'accion' => $request->query('accion'),
            'perPage' => 9999
        ]);
        $datos = $res['content']['data'];
        return Excel::download(new EvaluacionAuditoria($datos), 'evaluaciones_filtradas.xlsx');
    }



}
