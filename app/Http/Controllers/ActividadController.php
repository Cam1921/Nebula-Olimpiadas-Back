<?php

namespace App\Http\Controllers;

use App\Services\ActividadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActividadController extends Controller
{
    protected $actividadService;
    public function __construct(ActividadService $actividadService)
    {
        $this->actividadService = $actividadService;
    }
    public function index()
    {
        try {
            $actividares = $this->actividadService->listarActividades();
            if (!$actividares) {
                return response()->json([
                    'status' => 'success',
                    'data' => [],
                    'message' => 'No hay actividades disponibles',

                ], 200);
            }
            return response()->json([
                'status' => 'success',
                'data' => $actividares,
                'message' => 'Actividades obtenidas correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Error al obtener las actividades: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        //
    }
    public function show($id)
    {
        try {
            $actividad = $this->actividadService->obtenerActividad($id);
            if (!$actividad) {
                return response()->json(['state' => 'error', 'error' => 'Actividad no encontrada'], 404);
            }
            return response()->json(['state' => 'success', 'data' => $actividad, 'message' => 'Actividad obtenida correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Error al obtener la actividad: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $result = $this->actividadService->actualizarActividad($id, $request->all());

            return response()->json($result['content'], $result['status_code']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    public function destroy($id)
    {

        //
    }
    public function verificarActividad($nombreFase, $nombreActividad)
    {
        return $this->actividadService->verificarActividadPorNombreYFase($nombreFase, $nombreActividad);
    }
    public function porFase($faseId)
    {
        try {
            $actividades = $this->actividadService->getActividadesPorFase($faseId);
            if (!$actividades) {
                return response()->json([
                    'status' => 'success',
                    'data' => [],
                    'message' => 'No hay actividades disponibles',

                ], 200);
            }
            return response()->json([
                'status' => 'success',
                'data' => $actividades,
                'message' => 'Actividades obtenidas correctamente por fase',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Error al obtener las actividades por fase: ' . $e->getMessage(),
            ], 500);
        }
    }
}
