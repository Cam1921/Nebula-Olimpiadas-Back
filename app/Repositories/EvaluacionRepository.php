<?php

namespace App\Repositories;

use App\Models\Evaluacion;
use App\Models\Nivel;
use App\Models\NivelGrado;
use App\Models\Persona;
use App\Services\AreaNivelService;
use App\Traits\ApiResponseTrait;
use App\Traits\NormalizeStringTrait;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class EvaluacionRepository
{
    use NormalizeStringTrait;
    use ApiResponseTrait;
    protected $areaNivelFaseRepo;
    protected $areaNivelService;

    public function __construct(AreaNivelFaseRepository $areaNivelFaseRepo, AreaNivelService $areaNivelService)
    {
        $this->areaNivelFaseRepo = $areaNivelFaseRepo;
        $this->areaNivelService = $areaNivelService;
    }

    public function getEstadosByEvaluador(int $evaluadorId, ?int $faseId = null)
    {
        // Cargar todas las relaciones necesarias (evita N+1)
        $query = $this->areaNivelFaseRepo->getAllWithEvaluaciones();

        // Filtramos por fase si se especifica
        if ($faseId !== null) {
            $query = $query->where('id_fase', $faseId);
        }

        // Filtrar por asignación del evaluador
        $areaNivelFases = $query->filter(function ($anf) use ($evaluadorId) {
            return $anf->area_nivel
                && $anf->area_nivel->asignacions
                && $anf->area_nivel->asignacions->contains('id_persona', $evaluadorId);
        });

        $resultado = [];

        foreach ($areaNivelFases as $anf) {
            $areaNivel = $anf->area_nivel;

            if (!$areaNivel)
                continue;

            $resumen = $this->areaNivelService->getResumenEvaluaciones($areaNivel);
            $progreso = $this->areaNivelService->getProgresoEvaluacion($areaNivel);
            $responsable = $this->areaNivelService->getResponsableAreaNivel($areaNivel);

            $resultado[] = [
                'id_area_nivel_fase' => $anf->id,
                'fase' => $anf->fase?->nombre ?? 'Sin fase',
                'area' => $areaNivel->area?->nombre_area ?? 'Sin área',
                'nivel' => $areaNivel->nivel?->nombre_nivel ?? 'Sin nivel',
                'responsable' => $responsable
                    ? $responsable->nombres . ' ' . $responsable->apellidos
                    : 'Sin responsable',
                'estado' => $anf->estado, // ✅ estado real de area_nivel_fase
                'resumen_evaluaciones' => $resumen,
                'progreso_evaluacion' => $progreso,
            ];
        }

        return $this->successResponse('Estados obtenidos correctamente', $resultado);
    }
    public function obtenerEvaluacionesFiltradas(
        ?int $idFase = null,
        ?int $idArea = null,
        ?int $idNivel = null,
        ?string $nivelNombre = null,
        ?string $busqueda = null,
        int $perPage = 10,
        int $page = 1,
        ?string $estado_clasificado = null
    ): LengthAwarePaginator {

        $query = Evaluacion::with([
            'fase',
            'inscripcion.competidor.grado',
            'inscripcion.area_nivel.area',
            'inscripcion.area_nivel.nivel'
        ])
            ->where('estado_confirmacion', '=', 'aprobado');

        // 📌 Filtro por fase
        if ($idFase) {
            $query->where('id_fase', $idFase);
        }

        // 📌 Filtro por área y nivel
        if ($idArea || $idNivel) {
            $query->whereHas('inscripcion.area_nivel', function ($q) use ($idArea, $idNivel) {
                if ($idArea) {
                    $q->where('id_area', $idArea);
                }
                if ($idNivel) {
                    $q->where('id_nivel', $idNivel);
                }
            });
        }
        if ($nivelNombre) {
            $query->whereHas('inscripcion.competidor.grado', function ($q) use ($nivelNombre) {
                $q->where('nombre_grado', 'ILIKE', "%{$nivelNombre}%");
            });
        }

        // 🔍 Filtro por búsqueda (nombre, apellido, ci)
        if ($busqueda) {
            $query->whereHas('inscripcion.competidor', function ($q) use ($busqueda) {
                $q->where('nombres', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('apellidos', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('ci', 'ILIKE', "%{$busqueda}%");
            });
        }

        // ⚙️ Filtro por estado_clasificado
        if ($estado_clasificado) {
            switch (strtolower($estado_clasificado)) {
                case 'clasificados':
                    $query->whereNotNull('nota')
                        ->where('nota', '>=', 51)
                        ->where('respeto', true)
                        ->where('integridad', true)
                        ->where('puntualidad', true);
                    break;

                case 'no_clasificados':
                    $query->whereNotNull('nota')
                        ->where('nota', '<', 51)
                        ->where('respeto', true)
                        ->where('integridad', true)
                        ->where('puntualidad', true);
                    break;

                case 'descalificados':
                    $query->whereNotNull('nota')
                        ->where(function ($q) {
                            $q->where('respeto', false)
                                ->orWhere('integridad', false)
                                ->orWhere('puntualidad', false);
                        });
                    break;
            }
        }

        $query->orderBy('id', 'asc');

        $evaluaciones = $query->paginate($perPage, ['*'], 'page', $page);


        return $evaluaciones;
    }
    public function update(Evaluacion $evaluacion, array $data)
    {
        return tap($evaluacion)->update($data);
    }
    public function findById($id)
    {
        return Evaluacion::findOrFail($id);
    }

}