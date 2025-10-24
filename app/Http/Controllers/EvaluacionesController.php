<?php

namespace App\Http\Controllers;

use App\Exports\EvaluacionesExport;
use App\Services\EvaluacionesService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @OA\Tag(
 *     name="Evaluador",
 *     description="Operaciones relacionadas con evaluadores"
 * )
 */

class EvaluacionesController extends Controller
{
    use ApiResponseTrait;
    protected $evaluacionesService;

    public function __construct(EvaluacionesService $evaluacionesService)
    {
        $this->evaluacionesService = $evaluacionesService;
    }

    /**
     * @OA\Get(
     *     path="/api/evaluador/evaluaciones",
     *     summary="Obtener evaluaciones de un evaluador",
     *     tags={"Evaluador"},
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         description="Cantidad de resultados por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="busqueda",
     *         in="query",
     *         description="Filtrar evaluaciones por nombre o CI",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluaciones obtenidas correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id_evaluacion", type="integer"),
     *                     @OA\Property(property="id_inscrito", type="integer"),
     *                     @OA\Property(property="ci", type="string"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="area", type="string"),
     *                     @OA\Property(property="nivel", type="string"),
     *                     @OA\Property(property="nota", type="number", nullable=true),
     *                     @OA\Property(
     *                         property="conducta",
     *                         type="object",
     *                         @OA\Property(property="respeto", type="boolean", example=true),
     *                         @OA\Property(property="integridad", type="boolean", example=true),
     *                         @OA\Property(property="puntualidad", type="boolean", example=false)
     *                     ),
     *                     @OA\Property(property="descripcion", type="string", nullable=true),
     *                     @OA\Property(property="estado", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontraron evaluaciones"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $idEvaluador = auth()->guard('sanctum')->user()->personas()->first()->id;
            $perPage = $request->query('perPage', 10);
            $page = $request->query('page', 1);
            $busqueda = $request->query('busqueda', null);
            $estado_clasificado = $request->query('estado_clasificado', null);
            $evaluaciones = $this->evaluacionesService->obtenerEvaluacionesPorEvaluador($idEvaluador, $busqueda, $perPage, $page, $estado_clasificado);

            return response()->json(
                $this->successResponse(
                    'Evaluaciones obtenidas correctamente.',
                    $evaluaciones->items(),
                    [
                        'current_page' => $evaluaciones->currentPage(),
                        'per_page' => $evaluaciones->perPage(),
                        'total' => $evaluaciones->total(),
                        'last_page' => $evaluaciones->lastPage(),
                        'next_page_url' => $evaluaciones->nextPageUrl(),
                        'prev_page_url' => $evaluaciones->previousPageUrl(),
                        'links' => $evaluaciones->linkCollection(),
                    ]
                ),
                200
            );

        } catch (\Exception $e) {

            $code = str_contains($e->getMessage(), 'No se encontraron') ? 404 : 500;

            return response()->json(
                $this->errorResponse(
                    'ServerError',
                    $e->getMessage(),
                    [],
                    $code
                ),
                $code
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/evaluadores/evaluaciones/{id}",
     *     operationId="updateEvaluacion",
     *     tags={"Evaluador"},
     *     summary="Actualiza una evaluación existente",
     *     description="Permite actualizar la nota, descripción y los aspectos de conducta de una evaluación. Solo se envían los campos que se desean actualizar.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la evaluación a actualizar",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nota", type="number", format="float", example=85),
     *             @OA\Property(property="descripcion", type="string", example="Buen desempeño general"),
     *             @OA\Property(
     *                 property="conducta",
     *                 type="object",
     *                 @OA\Property(property="respeto", type="boolean", example=true),
     *                 @OA\Property(property="integridad", type="boolean", example=true),
     *                 @OA\Property(property="puntualidad", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluación actualizada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nota", type="number", format="float", example=85),
     *                     @OA\Property(property="descripcion", type="string", example="Buen desempeño general"),
     *                     @OA\Property(
     *                         property="conducta",
     *                         type="object",
     *                         @OA\Property(property="respeto", type="boolean", example=true),
     *                         @OA\Property(property="integridad", type="boolean", example=true),
     *                         @OA\Property(property="puntualidad", type="boolean", example=false)
     *                     ),
     *                     @OA\Property(property="estado_clasificacion", type="string", example="clasificado"),
     *                     @OA\Property(property="id_inscripcion", type="integer", example=3),
     *                     @OA\Property(property="id_fase", type="integer", example=3),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-18T11:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-18T11:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Evaluación no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No se encontró la evaluación."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="ServerError"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */



    public function update(Request $request, $id)
    {

        try {
            $request->validate([
                'nota' => 'required|numeric|min:0|max:100',
                'descripcion' => 'nullable|string|max:500',
                'conducta.respeto' => 'nullable|boolean',
                'conducta.integridad' => 'nullable|boolean',
                'conducta.puntualidad' => 'nullable|boolean',
            ], [
                'nota.required' => 'La nota es obligatoria.',
                'nota.numeric' => 'La nota debe ser un número.',
                'nota.min' => 'La nota no puede ser menor a 0.',
                'nota.max' => 'La nota no puede ser mayor a 100.',
            ]);

            // Obtener id del evaluador desde auth
            $evaluadorId = auth()->guard('sanctum')->user()->id;

            // Obtener IP del cliente
            $ip = $request->ip();

            $evaluacion = $this->evaluacionesService->actualizarEvaluacion($id, $request->all(), $evaluadorId, $ip);

            return response()->json(

                $this->successResponse(
                    'Evaluación actualizada correctamente.',
                    [$evaluacion]
                )
            );

        } catch (\Exception $e) {

            $code = str_contains($e->getMessage(), 'No se encontró') ? 404 : 500;

            return response()->json(
                $this->errorResponse(
                    'ServerError',
                    $e->getMessage(),
                    [],
                    $code
                ),
                $code
            );
        }

    }
    public function exportarExcel(Request $request)
    {
        try {
            $idEvaluador = auth()->guard('sanctum')->user()->personas()->first()->id;
            $busqueda = $request->query('busqueda', null);
            $estado_clasificado = $request->query('estado_clasificado', null);

            return Excel::download(
                new EvaluacionesExport($idEvaluador, $busqueda, $estado_clasificado),
                'competidores.xlsx',
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al generar Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
