<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AreaNivel;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class CompetidorController extends Controller
{
    /**
     * Generar listas de competidores con filtros y paginación
     * Endpoint: GET /api/competidores/listar?gestion=2025&area_id=1&nivel_id=1&page=1&per_page=10
     */
    public function listar(Request $request)
    {
        try {
            $gestion = $request->input('gestion', now()->year);
            $areaId = $request->input('area_id');
            $nivelId = $request->input('nivel_id');
            $page = max((int) $request->input('page', 1), 1);
            $perPage = max((int) $request->input('per_page', 10), 1);

            // Validaciones básicas
            if ($areaId && !is_numeric($areaId)) {
                return response()->json([
                    'error' => true,
                    'message' => 'El parámetro area_id debe ser numérico.'
                ], 400);
            }

            if ($nivelId && !is_numeric($nivelId)) {
                return response()->json([
                    'error' => true,
                    'message' => 'El parámetro nivel_id debe ser numérico.'
                ], 400);
            }

            $query = AreaNivel::with([
                'area',
                'nivel',
                'inscripcions.competidor.institucion',
                'inscripcions.competidor.tutor_competidor'
            ])->whereHas('inscripcions', function ($q) use ($gestion) {
                $q->where('gestion', $gestion);
            });

            if ($areaId) $query->where('id_area', $areaId);
            if ($nivelId) $query->where('id_nivel', $nivelId);

            $areaNiveles = $query->get();

            if ($areaNiveles->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'No se encontraron competidores para los filtros seleccionados.'
                ], 404);
            }

            $competidores = $areaNiveles->flatMap(function ($an) {
                return $an->inscripcions->map(function ($inscripcion) use ($an) {
                    $c = $inscripcion->competidor;
                    return [
                        'nombre' => $c->nombre_completo,
                        'ci' => $c->ci,
                        'unidad_educativa' => $c->institucion?->nombre_institucion ?? '-',
                        'departamento' => $c->institucion?->departamento_institucion ?? '-',
                        'area' => $an->area->nombre_area,
                        'nivel' => $an->nivel->nombre_nivel,
                        'tutor_academico' => $c->tutor_competidor?->nombre_completo ?? '-',
                    ];
                });
            })->values();

            // Paginación manual
            $total = $competidores->count();
            $items = $competidores->slice(($page - 1) * $perPage, $perPage)->values();

            $paginator = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return response()->json([
                'gestion' => $gestion,
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
                'data' => $paginator->items()
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'No se encontraron registros en la base de datos.'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error listar competidores: '.$e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Ocurrió un error inesperado al procesar la solicitud.'
            ], 500);
        }
    }
}
