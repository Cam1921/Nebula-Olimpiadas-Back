<?php

namespace App\Http\Controllers;

use App\Repositories\AreaRepository;
use App\Repositories\NivelRepository;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    // Devuelve solo áreas protected $areaRepo;
    protected $areaRepo;
    protected $nivelRepo;

    public function __construct(AreaRepository $areaRepo, NivelRepository $nivelRepo)
    {
        $this->areaRepo = $areaRepo;
        $this->nivelRepo = $nivelRepo;
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

    // Devuelve todo junto
    public function catalogos()
    {
        return response()->json([
            'areas' => $this->areaRepo->getAll(),
            'niveles' => $this->nivelRepo->getAll()
        ]);
    }
}
