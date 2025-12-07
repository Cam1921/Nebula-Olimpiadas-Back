<?php

namespace App\Http\Controllers;

use App\Services\FaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
class FaseController extends Controller
{
    protected $faseService;

    public function __construct(FaseService $faseService)
    {
        $this->faseService = $faseService;
    }

    public function index()
    {
        try {
            $fases = $this->faseService->listarFases();
            if (!$fases) {
                return response()->json(['status' => 'success', 'data' => [], 'message' => 'No hay fases disponibles'], 200);
            }
            return response()->json(['status' => 'success', 'data' => $fases, 'message' => 'Fases obtenidas correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener las fases: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener las fases'], 500);
        }




    }
    public function show($id)
    {
        try {
            $fase = $this->faseService->obtenerFase($id);
            if (!$fase) {
                return response()->json(['status' => 'error', 'error' => 'Fase no encontrada'], 404);
            }
            return response()->json(['status' => 'success', 'data' => $fase, 'message' => 'Fase obtenida correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $fase = $this->faseService->crear($request->all());
            return response()->json(['message' => 'Fase creada correctamente', 'data' => $fase], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $result = $this->faseService->actualizarFase($id, $request->all());
            return response()->json($result['content'], $result['status_code']);
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
    public function verificarFase($nombreFase)
    {
        return $this->faseService->verificarEstado($nombreFase);
    }
    public function obtenerFases()
    {

        try {
            $fases = $this->faseService->obtenerFases();
            if (!$fases) {
                return response()->json(['status' => 'success', 'data' => [], 'message' => 'No hay fases disponibles'], 200);
            }
            return response()->json(['status' => 'success', 'data' => $fases, 'message' => 'Fases obtenidas correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener las fases'], 500);
        }

    }
    public function publicarTodo()
    {
        try {
            // Actualizar todas las fases en borrador a publicado
            DB::table('fase')
                ->where('estado_publicado', 'borrador')
                ->update(['estado_publicado' => 'publicado']);

            // Actualizar todas las actividades en borrador a publicado
            DB::table('actividad')
                ->where('estado_publicado', 'borrador')
                ->update(['estado_publicado' => 'publicado']);

            return response()->json([
                'status' => 'success',
                'message' => 'Todas las fases y actividades en borrador fueron publicadas.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al publicar: ' . $e->getMessage()
            ], 500);
        }
    }
}
