<?php

namespace App\Http\Controllers;

use App\Models\AreaNivel;
use App\Models\ConfigMedallero;
use App\Services\MedalleroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedalleroController extends Controller
{
    protected $medalleroService;
    public function __construct(MedalleroService $medalleroService)
    {
        $this->medalleroService = $medalleroService;
    }
    public function index()
    {
        $areas = AreaNivel::with(['area', 'nivel', 'inscripcions', 'config_medalleros'])
            ->get()
            ->map(function ($item) {
                $config = $item->config_medalleros->first();

                return [
                    'id_area_nivel' => $item->id,
                    'area' => $item->area->nombre_area,
                    'nivel' => $item->nivel->nombre_nivel,
                    'participantes' => $item->inscripcions->count(),
                    'oros' => $config->oros ?? 0,
                    'platas' => $config->platas ?? 0,
                    'bronces' => $config->bronces ?? 0,
                    'menciones_honorificas' => $config->menciones_honorificas ?? 0,
                ];
            });

        // Calcular totales
        $total_oros = $areas->sum('oros');
        $total_platas = $areas->sum('platas');
        $total_bronces = $areas->sum('bronces');
        $total_menciones = $areas->sum('menciones_honorificas');
        $total_premios = $total_oros + $total_platas + $total_bronces + $total_menciones;

        return response()->json([
            'data' => $areas,
            'meta' => [
                'total_oros' => $total_oros,
                'total_platas' => $total_platas,
                'total_bronces' => $total_bronces,
                'total_menciones' => $total_menciones,
                'total_premios' => $total_premios,
            ]
        ]);
    }

    public function show($id)
    {
        //
    }
    public function store(Request $request)
    {
        //    
    }
    // POST /config-medallero/save-all
    public function saveAll(Request $request)
    {
        $data = $request->validate([
            'configs' => 'required|array',
            'configs.*.id_area_nivel' => 'required|integer|exists:area_nivel,id',
            'configs.*.oros' => 'required|integer|min:0',
            'configs.*.platas' => 'required|integer|min:0',
            'configs.*.bronces' => 'required|integer|min:0',
            'configs.*.menciones_honorificas' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['configs'] as $cfg) {
                ConfigMedallero::updateOrCreate(
                    ['id_area_nivel' => $cfg['id_area_nivel']],
                    [
                        'oros' => $cfg['oros'],
                        'platas' => $cfg['platas'],
                        'bronces' => $cfg['bronces'],
                        'menciones_honorificas' => $cfg['menciones_honorificas']
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Configuración del medallero guardada correctamente'
        ]);
    }
}
