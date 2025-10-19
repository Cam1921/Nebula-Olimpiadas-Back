<?php

namespace App\Http\Controllers;

use App\Services\FaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Fases",
 *     description="Gestión de las fases del proceso de evaluación"
 * )
 *
 * @OA\Get(
 *     path="/api/fases",
 *     summary="Listar todas las fases",
 *     tags={"Fases"},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de fases obtenida exitosamente"
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/fases",
 *     summary="Registrar una nueva fase",
 *     tags={"Fases"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"nombre","estado","fecha_inicio","fecha_fin"},
 *             @OA\Property(property="nombre", type="string", example="Fase Eliminatoria"),
 *             @OA\Property(property="descripcion", type="string", example="Primera ronda de evaluaciones"),
 *             @OA\Property(property="estado", type="string", example="abierto"),
 *             @OA\Property(property="fecha_inicio", type="string", format="date", example="2025-10-01"),
 *             @OA\Property(property="fecha_fin", type="string", format="date", example="2025-10-15")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Fase creada correctamente"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación en los datos enviados"
 *     )
 * )
 *
 * @OA\Patch(
 *     path="/api/fases/{id}",
 *     summary="Actualizar parcialmente una fase",
 *     tags={"Fases"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de la fase a actualizar",
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="nombre", type="string", example="Fase Final"),
 *             @OA\Property(property="descripcion", type="string", example="Última etapa de la competencia"),
 *             @OA\Property(property="estado", type="string", example="cerrado"),
 *             @OA\Property(property="fecha_inicio", type="string", format="date", example="2025-10-20"),
 *             @OA\Property(property="fecha_fin", type="string", format="date", example="2025-10-30")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Fase actualizada correctamente"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Fase no encontrada"
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/fases/{id}",
 *     summary="Eliminar una fase existente",
 *     tags={"Fases"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de la fase a eliminar",
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Fase eliminada correctamente"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Fase no encontrada"
 *     )
 * )
 */

class ControlFaseController extends Controller
{
    protected $faseService;

    public function __construct(FaseService $faseService)
    {
        $this->faseService = $faseService;
    }

    public function index(Request $request)
    {
        $busqueda = $request->query('busqueda');
        $perPage = $request->query('per_page', 10);
        $fases = $this->faseService->listar($busqueda, $perPage);

        return response()->json($fases);
    }

    public function store(Request $request)
    {
        try {
            $fase = $this->faseService->crear($request->all());
            return response()->json(['message' => 'Fase creada correctamente', 'fase' => $fase], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $fase = $this->faseService->actualizar($id, $request->all());
            return response()->json(['message' => 'Fase actualizada correctamente', 'fase' => $fase], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $this->faseService->eliminar($id);
            return response()->json(['message' => 'Fase eliminada correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
