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
    public function descargarErrores(Request $request)
    {
        $import_id = $request->input('import_id');
        $errores = $this->competidoresService->getErroresCsv($import_id);

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
    public function confirmar(Request $request)
    {
        $import_id = $request->input('import_id');
        $resultado = $this->competidoresService->confirmarCsvImportId($import_id);
        return response()->json($resultado, $resultado['code'] ?? 201);
    }
}
