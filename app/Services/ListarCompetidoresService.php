<?php

namespace App\Services;
use App\Models\Competidor;
use DB;
use Illuminate\Support\Facades\Log;

class ListarCompetidoresService
{
    /**
     * Obtiene un listado paginado de competidores con filtros opcionales.
     * @param int|null $areaId ID del área para filtrar (opcional)
     * @param int|null $nivelId ID del nivel para filtrar (opcional)
     * @param string|null $busqueda Texto de búsqueda por nombre, CI o institución (opcional)
     * @param int $page Número de página actual
     * @param int $perPage Cantidad de registros por página
     * @return array{
     *   status_code: int,
     *   content: array{
     *     status: string,
     *     data?: array,
     *     meta?: array{
     *       pagination: array{
     *         total: int,
     *         per_page: int,
     *         current_page: int,
     *         last_page: int
     *       }
     *     },
     *     message?: string
     *   }
     * }
     */
    public function listarCompetidores(?int $areaId, ?int $nivelId, ?string $busqueda, int $page, int $perPage)
    {
        try {
            $query = Competidor::query()
                ->select([
                    'competidor.id',
                    DB::raw("CONCAT(competidor.nombres, ' ', competidor.apellidos) as nombres"),
                    'competidor.ci as ci',
                    'grado.nombre_grado as grado',
                    'institucion.nombre_institucion as unidad_educativa',
                    'institucion.departamento_institucion as departamento',
                    'area.nombre_area as area',
                    'nivel.nombre_nivel as nivel',
                    'tutor_legal.telefono as contacto_tutor_legal',
                    'tutor_academico.telefono as contacto_tutor_academico',
                    'equipo.nombre_equipo as nombre_equipo',
                ])
                ->join('inscripcion', 'inscripcion.id_competidor', '=', 'competidor.id')
                ->join('area_nivel', 'area_nivel.id', '=', 'inscripcion.id_area_nivel')
                ->join('area', 'area.id', '=', 'area_nivel.id_area')
                ->join('nivel', 'nivel.id', '=', 'area_nivel.id_nivel')
                ->leftJoin('equipo', 'equipo.id', '=', 'competidor.id_equipo')
                ->leftJoin('institucion', 'institucion.id', '=', 'competidor.id_institucion')
                ->leftJoin('grado', 'grado.id', '=', 'competidor.id_grado')
                ->leftJoin('tutor as tutor_legal', 'tutor_legal.id', '=', 'competidor.id_tutor_legal')
                ->leftJoin('tutor as tutor_academico', 'tutor_academico.id', '=', 'inscripcion.id_tutor_academico');


            // Aplicar filtros si se proporcionan
            if ($areaId) {
                $query->where('area_nivel.id_area', $areaId);
            }

            if ($nivelId) {
                $query->where('area_nivel.id_nivel', $nivelId);
            }
            if ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where(DB::raw("CONCAT(competidor.nombres, ' ', competidor.apellidos)"), 'LIKE', "%{$busqueda}%")
                        ->orWhere('competidor.ci', 'LIKE', "%{$busqueda}%")
                        ->orWhere('institucion.nombre_institucion', 'LIKE', "%{$busqueda}%");
                });
            }

            // Paginación directamente en la base de datos
            $competidores = $query->paginate($perPage, ['*'], 'page', $page);
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'data' => $competidores->items(),
                    'meta' => [
                        'pagination' => [
                            'total' => $competidores->total(),
                            'per_page' => $competidores->perPage(),
                            'current_page' => $competidores->currentPage(),
                            'last_page' => $competidores->lastPage(),
                        ]
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error listar competidores: ' . $e->getMessage());
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error listar competidores:' . $e->getMessage(),
                ],
            ];
        }
    }
}

