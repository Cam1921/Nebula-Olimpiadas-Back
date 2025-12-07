<?php

namespace App\Http\Controllers;

use App\Models\AreaNivel;
use App\Models\Asignacion;
use App\Models\Evaluacion;
use App\Models\Fase;
use App\Models\Nivel;
use App\Models\PersonaArea;
use App\Services\AsignacionService;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;

class AsignacionController extends Controller
{
    use ApiResponseTrait;
    protected $asignacionService;
    public function __construct(AsignacionService $asignacionService)
    {
        $this->asignacionService = $asignacionService;
    }
    public function asignarInscritos(Request $request)
    {
        $request->validate([
            'id_area' => 'required|integer',
            'id_nivel' => 'required|integer',
            'limite_por_evaluador' => 'required|integer|min:1',
            'cantidad_evaluadores' => 'required|integer|min:1'
        ]);

        $idArea = $request->id_area;
        $idNivel = $request->id_nivel;
        $limite = $request->limite_por_evaluador;
        $cantidadEvaluadores = $request->cantidad_evaluadores;

        $faseActiva = Fase::where('estado', 'activa')
            ->whereIn('nombre', ['clasificacion', 'final'])
            ->first();

        if (!$faseActiva) {
            return response()->json([
                'state' => 'error',
                'message' => 'No existe una fase activa o no corresponde a las fases de clasificación o final.'
            ], 400);
        }

        // ===============================
        // 1. GUARDAR LÍMITES EN NIVEL
        // ===============================
        $nivel = Nivel::find($idNivel);

        if (!$nivel) {
            return response()->json([
                'state' => 'error',
                'message' => 'El nivel especificado no existe.'
            ], 400);
        }

        // Guardar el límite y la cantidad de evaluadores (NUEVO)
        $nivel->limite_evaluador = $limite;
        $nivel->cantidad_evaluadores = $cantidadEvaluadores; // NUEVO
        $nivel->save();

        // ===============================
        // 2. OBTENER ÁREA_NIVEL
        // ===============================
        $areaNivel = AreaNivel::where('id_area', $idArea)
            ->where('id_nivel', $idNivel)
            ->first();

        if (!$areaNivel) {
            return response()->json([
                'state' => 'error',
                'message' => 'El área y nivel especificados no existen.'
            ], 400);
        }

        $idAreaNivel = $areaNivel->id;

        // ===============================
        // 3. OBTENER TODOS LOS EVALUADORES
        // ===============================
        $evaluadores = Asignacion::where('id_area_nivel', $idAreaNivel)
            ->whereHas('persona.rols', function ($q) {
                $q->where('nombre', 'evaluador');
            })
            ->get();

        if ($evaluadores->isEmpty()) {
            return response()->json([
                'state' => 'error',
                'message' => 'No existen evaluadores para este área y nivel.'
            ], 400);
        }

        // ===============================
        // 4. FILTRAR SOLO LA CANTIDAD SOLICITADA   (NUEVO)
        // ===============================
        if ($evaluadores->count() < $cantidadEvaluadores) {
            return response()->json([
                'state' => 'error',
                'message' => 'No hay suficientes evaluadores registrados para cumplir la cantidad requerida.'
            ], 400);
        }

        // Seleccionar solo N evaluadores aleatorios
        $evaluadores = $evaluadores->random($cantidadEvaluadores)->values(); // NUEVO

        // ===============================
        // 5. OBTENER TODAS LAS EVALUACIONES
        // ===============================
        $evaluaciones = Evaluacion::where('id_fase', $faseActiva->id)
            ->whereHas('inscripcion', function ($q) use ($idAreaNivel) {
                $q->where('id_area_nivel', $idAreaNivel);
            })
            ->inRandomOrder()
            ->get();

        if ($evaluaciones->isEmpty()) {
            return response()->json([
                'state' => 'warning',
                'message' => 'No existen inscritos para asignar en este área y nivel.'
            ], 200);
        }

        // ===============================
        // 6. CONTADOR POR EVALUADOR
        // ===============================
        $contador = [];
        foreach ($evaluadores as $ev) {
            $contador[$ev->id] = 0;
        }

        // ===============================
        // 7. ASIGNACIÓN ALEATORIA
        // ===============================
        foreach ($evaluaciones as $eval) {
            $disponibles = collect($evaluadores)->filter(function ($e) use ($contador, $limite) {
                return $contador[$e->id] < $limite;
            });

            if ($disponibles->isEmpty()) {
                break;
            }

            $seleccionado = $disponibles->random();

            $eval->id_asignacion = $seleccionado->id;
            $eval->save();

            $contador[$seleccionado->id]++;
        }

        return response()->json([
            'state' => 'success',
            'message' => 'Asignación realizada correctamente.',
            'total_asignados' => $evaluaciones->count(),
            'evaluadores_usados' => $cantidadEvaluadores // NUEVO
        ]);
    }

    public function listar(Request $request)
    {
        $id_area = $request->input('id_area');
        $id_nivel = $request->input('id_nivel');

        // Si no envían ambos filtros, devolver vacío
        if (!$id_area || !$id_nivel) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'meta' => [
                    'limite_por_evaluador' => null,
                    'total_evaluadores' => 0
                ]
            ]);
        }

        // Buscar fase ACTIVA
        $faseActiva = Fase::where('estado', 'activa')->first();

        if (!$faseActiva) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'meta' => [
                    'limite_por_evaluador' => null,
                    'total_evaluadores' => 0
                ],
                'message' => 'No existe una fase activa.'
            ]);
        }

        // Buscar el area_nivel exacto
        $areaNivel = AreaNivel::where('id_area', $id_area)
            ->where('id_nivel', $id_nivel)
            ->first();

        if (!$areaNivel) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'meta' => [
                    'limite_por_evaluador' => null,
                    'total_evaluadores' => 0
                ]
            ]);
        }

        // Límite por evaluador viene del Nivel
        $limiteEvaluador = $areaNivel->nivel->limite_evaluador;

        // Obtener asignaciones = evaluadores para ese área_nivel
        $asignaciones = $areaNivel->asignacions()
            ->whereHas('persona.rols', function ($q) {
                $q->where('nombre', 'evaluador');
            })
            ->with([
                'persona',
                'evaluacions' => function ($q) use ($faseActiva) {
                    $q->where('id_fase', $faseActiva->id); // ← CORRECTO
                },
                'evaluacions.inscripcion' => function ($q) use ($areaNivel) {
                    $q->where('id_area_nivel', $areaNivel->id); // Este filtro sí es correcto
                }
            ])
            ->get();
        // Formatear las cards
        $evaluadores = $asignaciones->map(function ($asignacion) use ($limiteEvaluador) {

            $cargaActual = $asignacion->evaluacions->count();
            $disponibles = max($limiteEvaluador - $cargaActual, 0);
            $estado = $cargaActual > 0 ? 'Activo' : 'Inactivo';

            return [
                'id' => $asignacion->id,
                'nombre' => $asignacion->persona->nombres . ' ' . $asignacion->persona->apellidos,
                'estado' => $estado,
                'area' => $asignacion->area_nivel->area->nombre_area,
                'nivel' => $asignacion->area_nivel->nivel->nombre_nivel,
                'carga_actual' => $cargaActual,
                'limite' => $limiteEvaluador,
                'espacios_disponibles' => $disponibles,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $evaluadores,
            'meta' => [
                'limite_por_evaluador' => $limiteEvaluador,
                'total_evaluadores' => $evaluadores->count(),
                'limite_cantidad_evaluadores' => $areaNivel->nivel->cantidad_evaluadores
            ],
            'message' => 'Asignaciones obtenidas correctamente',
        ]);
    }


    public function store(Request $request, $idAreaNivel)
    {
        $evaluadores = $request->input('evaluadores');
        $res = $this->asignacionService->asignarEvaluadores($evaluadores, $idAreaNivel);
        return response()->json($res['content'], $res['status_code']);
    }

    public function destroy(Request $request, $idAreaNivel)
    {
        $evaluadores = $request->input('asignaciones');
        $res = $this->asignacionService->eliminarEvaluadores($evaluadores, $idAreaNivel);
        return response()->json($res['content'], $res['status_code']);
    }
}
