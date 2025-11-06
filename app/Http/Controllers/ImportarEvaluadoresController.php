<?php

namespace App\Http\Controllers;

use App\Services\ImportarEvaluadoresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportarEvaluadoresController extends Controller
{
    protected $importarEvaluadoresService;
    public function __construct(ImportarEvaluadoresService $importarEvaluadoresService)
    {
        $this->importarEvaluadoresService = $importarEvaluadoresService;
    }
    public function preview(Request $request): JsonResponse
    {
        $file = $request->file('archivo');
        $resultado = $this->importarEvaluadoresService->previewCsv($file);
        return response()->json(
            $resultado,
            $resultado['code'] ?? 200
        );
    }
    public function confirmar(Request $request): JsonResponse
    {
        $import_id = $request->input('import_id');
        $resultado = $this->importarEvaluadoresService->confirmarCsvImportId($import_id);
        return response()->json($resultado, $resultado['code'] ?? 201);
    }

    public function descargarErrores(Request $request)
    {
        $import_id = $request->input('import_id');
        $errores = $this->importarEvaluadoresService->getErroresCsv($import_id);

        if (!$errores) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron errores para este import_id'
            ], 404);
        }

        $filename = "errores_{$import_id}.csv";

        // Creamos un stream en memoria
        $handle = fopen('php://memory', 'r+');

        // 🔹 BOM UTF-8 para que Excel reconozca los acentos correctamente
        fwrite($handle, "\xEF\xBB\xBF");

        // Encabezados
        fputcsv($handle, ['Fila', 'Campo', 'Error']);

        // Contenido
        foreach ($errores as $e) {
            fputcsv($handle, [$e['row'], $e['field'], $e['error']]);
        }

        rewind($handle);

        // Enviar descarga con encabezados adecuados
        return response()->streamDownload(function () use ($handle) {
            fpassthru($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
