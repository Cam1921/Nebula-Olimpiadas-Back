<?php

namespace App\Http\Controllers;

use App\Exports\EvaluacionesExport;
use App\Exports\ListaResultadosExport;
use App\Models\AreaNivelFase;
use App\Models\Equipo;
use App\Models\Evaluacion;
use App\Repositories\EvaluacionRepository;
use App\Services\EvaluacionesService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /**
     * Summary of index
     * @param Request $request
     * @param mixed $idAreaNivelFase
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $idAreaNivelFase)
    {
        $perPage = $request->query('perPage', 10);
        $page = $request->query('page', 1);
        $busqueda = $request->query('busqueda', null);
        $estado_clasificado = $request->query('estado_clasificado', null);
        $ordenarPor = $request->query('ordenar_por', 'id');
        $direccion = $request->query('direccion', 'asc');
        $res = $this->evaluacionesService->obtenerEvaluaciones($idAreaNivelFase, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion);
        return response()->json($res['content'], $res['status_code']);
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

    /**
     * Summary of filtrar
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filtrar(Request $request)
    {
        $publicado = $request->query('esPublicado', false);
        $estado = $request->query('estado', null);
        $perPage = $request->query('perPage', 10);
        $id_fase = $request->query('id_fase', null);
        $id_area = $request->query('id_area', null);
        $id_nivel = $request->query('id_nivel', null);
        $nivelNombre = $request->query('nivel_nombre', null);
        $page = $request->query('page', 1);
        $busqueda = $request->query('busqueda', null);
        $estado_clasificado = $request->query('estado_clasificado', null);
        $ordenarPor = $request->query('ordenar_por', 'nombre');
        $direccion = $request->query('direccion', 'asc');
        /* $res = $this->evaluacionesService->filtrarEvaluaciones($nivelNombre, $id_area, $id_nivel, $id_fase, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion); */
        $res = $this->evaluacionesService->filtrarEvaluaciones($publicado, $estado, $nivelNombre, $id_fase, $id_area, $id_nivel, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion);
        /* $res = $this->evaluacionesService->filtrarEvaluacionesRanking($nivelNombre, $id_fase, $id_area, $id_nivel, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion); */


        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Summary of update
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $res = $this->evaluacionesService->actualizarEvaluacion($request, $id);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Summary of exportarExcel
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarExcel(Request $request)
    {
        try {
            $user = auth()->guard('sanctum')->user();
            $persona = $user->personas()->first();

            if (!$persona) {
                return response()->json([
                    'message' => 'El usuario no tiene persona asociada'
                ], 400);
            }

            $idEvaluador = $persona->id;
            $busqueda = $request->query('busqueda', null);
            $estado_clasificado = $request->query('estado_clasificado', 'todos');
            $ordenarPor = $request->query('ordenar_por', 'id');
            $direccion = $request->query('direccion', 'asc');


            // Nuevo parámetro: idAreaNivelFase
            $idAreaNivelFase = $request->query('idAreaNivelFase', null);
            if ($idAreaNivelFase !== null) {
                $idAreaNivelFase = (int) $idAreaNivelFase;
            }

            return Excel::download(
                new EvaluacionesExport($idEvaluador, $busqueda, $estado_clasificado, $idAreaNivelFase, $ordenarPor, $direccion),
                'competidores.xlsx'
            );

        } catch (\Throwable $e) {
            Log::error('Error al generar Excel', [
                'mensaje' => $e->getMessage(),
                'traza' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al generar Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary of getEstadosAllFases
     * @return array
     */
    public function getEstadosAllFases()
    {
        $evaluadorId = auth()->guard('sanctum')->user()->personas()->first()->id;
        return $this->evaluacionesService->getEstadosByEvaluador($evaluadorId);
    }

    /**
     * Summary of getEstadosPorFase
     * @param mixed $faseId
     * @return array
     */
    public function getEstadosPorFase($faseId)
    {
        $evaluadorId = auth()->guard('sanctum')->user()->personas()->first()->id;
        return $this->evaluacionesService->getEstadosByEvaluador($evaluadorId, $faseId);
    }

    /**
     * Summary of aprobarClasificados
     * @param mixed $idAreaNivelFase
     * @return \Illuminate\Http\JsonResponse
     */
    public function aprobarClasificados($idAreaNivelFase)
    {
        $res = $this->evaluacionesService->aprobarClasificadosEvaluador($idAreaNivelFase);
        return response()->json($res['content'], $res['status_code']);
    }
    /**
     * Summary of otorgarAval
     * @param mixed $idAreaNivelFase
     * @return \Illuminate\Http\JsonResponse
     */
    public function otorgarAval($idAreaNivelFase)
    {
        $res = $this->evaluacionesService->otorgarAvalResponsable($idAreaNivelFase);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Summary of exportarEvaluaciones
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarEvaluaciones(Request $request)
    {
        $res = app(EvaluacionesService::class)->filtrarEvaluaciones(
            $request->esPublicado ?? false,
            $request->estado ?? null,
            $request->nivel_nombre ?? null,
            $request->id_fase ?? null,
            $request->id_area ?? null,
            $request->id_nivel ?? null,
            $request->busqueda ?? null,
            99999,
            1,
            $request->estado_clasificado ?? null,
            $request->ordenar_por ?? 'nombre',
            $request->direccion ?? 'asc',
        );
        $datos = $res['content']['data'];
        $estado = $request->estado ?? null;


        return Excel::download(new ListaResultadosExport($datos, $estado), 'evaluaciones_filtradas.xlsx');
    }

    /**
     * Summary of obtenerCompetidoresEquipo
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerCompetidoresEquipo($id)
    {
        $res = $this->evaluacionesService->obtenerCompetidoresEquipo($id);
        return response()->json($res['content'], $res['status_code']);
    }

}
