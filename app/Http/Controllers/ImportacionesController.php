<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCompetidoresRequest;
use App\Services\ImportacionesService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportacionesController extends Controller
{
    protected $competidoresService;

    public function __construct(ImportacionesService $competidoresService)
    {
        $this->competidoresService = $competidoresService;
    }

    /**
     * Previsualiza un archivo CSV de competidores antes de ser importado.
     *
     * Valida el archivo, procesa el contenido y devuelve un resumen
     * indicando registros válidos, inválidos y errores detectados.
     * No guarda información definitiva en la base de datos.
     *
     * @param ImportCompetidoresRequest $request Request con el archivo CSV validado
     * @return JsonResponse Respuesta con el resultado del análisis del archivo
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
     * Descarga un archivo CSV con los errores encontrados durante la importación.
     *
     * Genera dinámicamente un archivo CSV con el detalle de los errores
     * asociados a un import_id específico, incluyendo fila, campo y mensaje.
     *
     * @param Request $request Request que contiene el import_id
     * @return JsonResponse|StreamedResponse Archivo CSV o mensaje de error
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

        //BOM UTF-8 para que Excel reconozca los acentos correctamente
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
    /**
     * Confirma la importación de competidores previamente previsualizada.
     *
     * Procesa definitivamente los registros válidos asociados al import_id
     * y los guarda en la base de datos.
     *
     * @param Request $request Request que contiene el import_id
     * @return JsonResponse Resultado de la importación
     */
    public function confirmar(Request $request)
    {
        $import_id = $request->input('import_id');
        $resultado = $this->competidoresService->confirmarCsvImportId($import_id);
        return response()->json($resultado, $resultado['code'] ?? 201);
    }
}
