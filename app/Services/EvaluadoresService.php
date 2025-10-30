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

    public function previewCsv($file)
    {
        if (!$file) {
            return $this->errorResponse(
                'file not found',
                'Archivo no encontrado o no es un CSV válido',
                [['field' => 'archivo', 'error' => 'Archivo inválido']],
                400
            );
        }
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        $headers = array_map(fn($h) => $this->normalizeString($h), $headers);

        $required = ['nombre', 'apellido', 'correo', 'telefono', 'ci', 'area'];
        $required_normalized = array_map(fn($h) => $this->normalizeString($h), $required);

        $missing_required = array_diff($required_normalized, $headers);

        if (!empty($missing_required)) {
            return $this->errorResponse(
                'requered headers missing',
                'Encabezados requeridos faltantes',
                [['field' => 'headers', 'error' => implode(', ', $missing_required)]],
                400,
                ['found_headers' => $headers]
            );
        }

        //falta completar


    }


}