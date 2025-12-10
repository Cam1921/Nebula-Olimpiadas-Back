<?php

namespace App\Http\Controllers;

use App\Models\AreaNivel;
use App\Models\Fase;
use App\Services\AreaNivelService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class AreaNivelController extends Controller
{
    use ApiResponseTrait;
    protected $areaNivelService;

    public function __construct(AreaNivelService $areaNivelService)
    {
        $this->areaNivelService = $areaNivelService;
    }

    public function index(Request $request)
    {
        $idArea = $request->query('id_area');
        $idNivel = $request->query('id_nivel');
        $perPage = $request->query('per_page', 10);

        // 1. Obtener la fase activa
        $faseActiva = Fase::where('estado', 'activa')->first();

        if (!$faseActiva) {
            return response()->json([
                'message' => 'No existe una fase activa.'
            ], 400);
        }

        $query = AreaNivel::withCount([
            'asignacions as evaluadores_count' => function ($q) {
                $q->whereHas('persona.rols', function ($r) {
                    $r->where('nombre', 'evaluador');
                });
            },

            // 2. Competidores SOLO de la fase activa
            'inscripcions as inscripcions_count' => function ($q) use ($faseActiva) {
                $q->whereHas('evaluacions', function ($e) use ($faseActiva) {
                    $e->where('id_fase', $faseActiva->id);
                });
            }

        ])->with(['area', 'nivel']);

        if ($idArea) {
            $query->where('id_area', $idArea);
        }

        if ($idNivel) {
            $query->where('id_nivel', $idNivel);
        }

        $areaNiveles = $query->paginate($perPage);

        $items = collect($areaNiveles->items())->map(function ($item) {
            return [
                'id_area_nivel' => $item->id,
                'area' => $item->area->nombre_area,
                'id_area' => $item->area->id,
                'nivel' => $item->nivel->nombre_nivel,
                'id_nivel' => $item->nivel->id,
                'total_evaluadores' => $item->evaluadores_count,
                'total_competidores' => $item->inscripcions_count, // ahora correcto
            ];
        });

        return response()->json(
            $this->successResponse(
                'Áreas y niveles obtenidos correctamente.',
                $items->toArray(),
                [
                    'current_page' => $areaNiveles->currentPage(),
                    'per_page' => $areaNiveles->perPage(),
                    'total' => $areaNiveles->total(),
                    'last_page' => $areaNiveles->lastPage(),
                ]
            ),
            200
        );
    }

    public function getEvaluadores(Request $request, $idAreaNivel)
    {
        $res = $this->areaNivelService->listaEvaluadores($request->all(), $idAreaNivel);

        return response()->json(
            $res['content']

            ,
            $res['status_code']
        );
    }


}
