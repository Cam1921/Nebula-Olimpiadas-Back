<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\NivelRepository;
use App\Services\CatalogoService;
use DB;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    // Devuelve solo áreas protected $areaRepo;
    protected $areaRepo;
    protected $nivelRepo;
    protected $catalogoService;

    public function __construct(AreaRepository $areaRepo, NivelRepository $nivelRepo, CatalogoService $catalogoService)
    {
        $this->areaRepo = $areaRepo;
        $this->nivelRepo = $nivelRepo;
        $this->catalogoService = $catalogoService;
    }

    // Devuelve áreas
    public function areas()
    {
        return response()->json($this->areaRepo->getAll());
    }

    // Devuelve niveles
    public function niveles()
    {
        return response()->json($this->nivelRepo->getAll());
    }
    public function fases()
    {
        $res = $this->catalogoService->getFases();
        return response()->json($res['content'], $res['status_code']);
    }
    public function roles()
    {
        $res = $this->catalogoService->getRoles();
        return response()->json($res['content'], $res['status_code']);
    }
    public function areaNiveles()
    {
        // Trae todas las áreas
        $areas = $this->areaRepo->getAll();

        $result = $areas->map(function ($area) {
            // Obtener los niveles asociados desde la tabla pivote
            $niveles = DB::table('area_nivel')
                ->join('nivel', 'area_nivel.id_nivel', '=', 'nivel.id')
                ->where('area_nivel.id_area', $area->id)
                ->select('nivel.id', 'nivel.nombre_nivel')
                ->get();

            return [
                'id' => $area->id,
                'nombre' => $area->nombre_area,
                'cantidad_evaluadores' => $area->cantidad_evaluadores,
                'niveles' => $niveles

            ];
        });


        return response()->json(
            [
                'status' => 'success',
                'message' => 'area y niveles obtendidos correctamente',
                'data' => $result
            ]

        );
    }

    // Devuelve todo junto
    public function catalogos()
    {
        return response()->json([
            'areas' => $this->areaRepo->getAll(),
            'niveles' => $this->nivelRepo->getAll()
        ]);
    }
}
