<?php
namespace App\Services;

use App\Models\Area;
use App\Models\Asignacion;
use App\Models\Persona;
use App\Models\PersonaArea;
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

    public function listEvaluadores(array $params)
    {
        $include = explode(',', $params['include'] ?? '');

        // Si solo se quiere área (sin nivel)
        if (in_array('areas', $include) && !in_array('area_nivel', $include)) {
            return $this->getEvaluadoresArea($params);
        }

        // Si se quiere área + nivel
        if (in_array('area_nivel', $include)) {
            return $this->getEvaluadoresAreaNivel($params);
        }

        // Por defecto, solo lista básica
        return [
            'status_code' => 201,
            'content' => [
                'status' => 'success',
                'message' => 'evaluadores listados',
                'data' => [],
                'meta' => [],
            ],

        ];
    }

    private function getEvaluadoresArea(array $params)
    {
        $areaId = $params['area_id'] ?? null;
        $search = $params['search'] ?? null;
        $perPage = $params['per_page'] ?? 10;

        $query = Persona::with([
            'user:id,email',
            'persona_areas.area:id,nombre_area',
        ])->whereHas('rols', fn($q) => $q->where('nombre', 'evaluador'));

        if ($areaId) {
            $query->whereHas('persona_areas', fn($q) => $q->where('id_area', $areaId));
        }

        if ($search) {
            $query->where(
                fn($q) =>
                $q->where('nombres', 'LIKE', "%$search%")
                    ->orWhere('apellidos', 'LIKE', "%$search%")
                    ->orWhere('ci', 'LIKE', "%$search%")
            );
        }
        $evaluadores = $query->paginate($perPage);
        $data = $evaluadores->getCollection()->transform(fn($persona) => [
            'id' => $persona->id,
            'nombre' => $persona->nombres,
            'apellidos' => $persona->apellidos,
            'ci' => $persona->ci,
            'correo' => $persona->user->email ?? null,
            'telefono' => '+591 ' . $persona->telefono,
            'area' => optional($persona->persona_areas->first()?->area)->nombre_area,
            'id_area' => optional($persona->persona_areas->first()?->area)->id,
            'fecha_registro' => optional($persona->created_at)->format('Y-m-d'),
        ]);

        $areas = $this->informacionAreas();
        $meta = [
            'current_page' => $evaluadores->currentPage(),
            'last_page' => $evaluadores->lastPage(),
            'per_page' => $evaluadores->perPage(),
            'total' => $evaluadores->total(),
            'total_evaluadores' => $areas['totalEvaluadores'],
            'areas_cubiertas' => $areas['areasCubiertas'],
            'areas_disponibles' => $areas['areasDisponibles'],
            'areas' => $areas['areas'],
        ];

        return [
            'status_code' => 201,
            'content' => [
                'status' => 'success',
                'message' => 'evaluadores listados',
                'data' => $data,
                'meta' => $meta,
            ],

        ];
    }

    private function informacionAreas()
    {
        $areas = Area::all(); // traemos todas las áreas
        $areasInfo = [];
        $totalEvaluadores = 0;
        $areasCubiertas = 0;

        foreach ($areas as $area) {
            $taken = PersonaArea::where('id_area', $area->id)
                ->whereHas('persona.rols', fn($q) => $q->where('nombre', 'evaluador'))->count();
            $available = $area->cantidad_evaluadores - $taken;

            // contar total de evaluadores
            $totalEvaluadores += $taken;

            // si ya hay al menos un evaluador, el área se considera cubierta
            if ($taken >= $area->cantidad_evaluadores) {
                $areasCubiertas++;
            }

            $areasInfo[] = [
                'id' => $area->id,
                'nombre' => $area->nombre_area,
                'max' => $area->cantidad_evaluadores,
                'ocupados' => $taken,
                'faltantes' => $available,
            ];
        }

        $areasDisponibles = $areas->count() - $areasCubiertas;

        return [
            'areas' => $areasInfo,
            'totalEvaluadores' => $totalEvaluadores,
            'areasCubiertas' => $areasCubiertas,
            'areasDisponibles' => $areasDisponibles,
        ];
    }

    private function getEvaluadoresAreaNivel(array $params)
    {
        $areaId = $params['area_id'] ?? null;
        $nivelId = $params['nivel_id'] ?? null;
        $search = $params['search'] ?? null;
        $perPage = $params['per_page'] ?? 10;

        $query = Asignacion::with([
            'persona:id,nombres,apellidos,ci,telefono,email',
            'persona.rols:id,nombre',
            'area_nivel.area:id,nombre_area',
            'area_nivel.nivel:id,nombre_nivel'
        ])->whereHas('persona.rols', fn($q) => $q->where('nombre', 'evaluador'));

        if ($areaId) {
            $query->whereHas('area_nivel', fn($q) => $q->where('id_area', $areaId));
        }

        if ($nivelId) {
            $query->whereHas('area_nivel', fn($q) => $q->where('id_nivel', $nivelId));
        }

        if ($search) {
            $query->whereHas(
                'persona',
                fn($q) =>
                $q->where('nombres', 'LIKE', "%$search%")
                    ->orWhere('apellidos', 'LIKE', "%$search%")
                    ->orWhere('ci', 'LIKE', "%$search%")
            );
        }
        $evaluadores = $query->paginate($perPage);
        $data = $evaluadores->getCollection()->transform(fn($asignacion) => [
            'id_asignacion' => $asignacion->id,
            'id' => $asignacion->id_persona,
            'nombre' => $asignacion->persona->nombres,
            'apellidos' => $asignacion->persona->apellidos,
            'ci' => $asignacion->persona->ci,
            'correo' => $asignacion->persona->email ?? null,
            'telefono' => '+591 ' . $asignacion->persona->telefono,
            'area' => $asignacion->area_nivel->area->nombre_area,
            'id_area' => $asignacion->area_nivel->area->id,
            'nivel' => $asignacion->area_nivel->nivel->nombre_nivel,
            'id_nivel' => $asignacion->area_nivel->nivel->id,
            'fecha_registro' => optional($asignacion->created_at)->format('Y-m-d'),
        ]);
        $meta = [
            'current_page' => $evaluadores->currentPage(),
            'last_page' => $evaluadores->lastPage(),
            'per_page' => $evaluadores->perPage(),
            'total' => $evaluadores->total(),
        ];
        return [
            'status_code' => 201,
            'content' => [
                'status' => 'success',
                'message' => 'evaluadores listados',
                'data' => $data,
                'meta' => $meta,
            ],

        ];
    }
}