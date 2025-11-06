<?php
namespace App\Services;

use App\Repositories\PersonaRepository;
use App\Traits\ApiResponseTrait;
use App\Traits\NormalizeStringTrait;

class EvaluadoresService
{
    use ApiResponseTrait;
    use NormalizeStringTrait;
    protected $personaRepository;

    public function __construct(PersonaRepository $personaRepository)
    {
        $this->personaRepository = $personaRepository;
    }

}