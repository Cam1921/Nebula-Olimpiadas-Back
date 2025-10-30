<?php

namespace App\Repositories;

use App\Models\Area;
use App\Models\AreaNivelFase;
use App\Traits\NormalizeStringTrait;

class AreaNivelFaseRepository extends AreaNivelFase
{
    protected $areaNivelRepo;

    public function __construct(AreaNivelRepository $areaNivelRepo)
    {
        $this->areaNivelRepo = $areaNivelRepo;
    }
    public function getAllWithEvaluaciones()
    {
        return AreaNivelFase::with([
            'fase',
            'area_nivel.area',
            'area_nivel.nivel',
        ])->get();
    }





}