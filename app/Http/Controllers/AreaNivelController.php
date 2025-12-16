<?php

namespace App\Http\Controllers;
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

    /**
     * Listar area-niveles
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $res = $this->areaNivelService->ListarAreaNiveles($request);
        return response()->json($res['content'], $res['status_code']);
    }

    /**
     * Lista evaluadores de un area-nivel
     * @param Request $request
     * @param mixed $idAreaNivel
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEvaluadores(Request $request, $idAreaNivel)
    {
        $res = $this->areaNivelService->listaEvaluadores($request->all(), $idAreaNivel);
        return response()->json($res['content'], $res['status_code']);
    }


}
