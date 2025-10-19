<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API Olimpiadas",
 *     version="1.0.0",
 *     description="Documentación de la API para evaluaciones y evaluadores"
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor local"
 * )
 */
class OpenApiInfo
{
    // Esta clase solo existe para contener las anotaciones de Swagger.
}

