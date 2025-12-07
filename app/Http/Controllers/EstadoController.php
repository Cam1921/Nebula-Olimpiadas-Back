<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Area;
use App\Models\AreaNivelFase;
use App\Models\Asignacion;
use App\Models\Evaluacion;
use App\Models\Fase;
use App\Models\Persona;
use App\Services\EstadosServide;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EstadoController extends Controller
{
    protected $estadoService;

    public function __construct(EstadosServide $estadoServide)
    {
        $this->estadoService = $estadoServide;
    }
    public function index()
    {
        // Opcional: filtrar por olimpiada, fase, área, nivel


        // Llamamos al servicio que devuelve resumen y progreso
        $resultado = $this->estadoService->getAllEstadoAreaNivel();

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }
    public function actualizarEstado(Request $request, $id)
    {
        try {
            $area = AreaNivelFase::findOrFail($id);

            $nuevoEstado = $request->input('estado');
            if (!$nuevoEstado) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Debe proporcionar un estado válido.',
                ], 400);
            }

            if ($area->estado === $nuevoEstado) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El estado actual ya es el mismo.',
                ], 400);
            }
            if ($nuevoEstado === "") {

            }

            $area->estado = $nuevoEstado;
            $area->save();

            return response()->json([
                'success' => true,
                'message' => "Estado actualizado correctamente a '{$nuevoEstado}'.",
                'data' => $area,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function getEstadosAreas()
    {
        $areas = Area::select('id', 'nombre_area')->get();

        $data = $areas->map(function ($area) {
            // Obtenemos todos los estados asociados al área
            $estados = AreaNivelFase::whereHas('area_nivel', function ($query) use ($area) {
                $query->where('id_area', $area->id);
            })->pluck('estado');

            if ($estados->isEmpty()) {
                $estadoGeneral = 'Sin estado';
            }
            // 🔹 Si alguna fase sigue "En_evaluacion", el área general está "En evaluación"
            elseif ($estados->contains(fn($e) => $e === 'En_evaluacion')) {
                $estadoGeneral = 'En evaluación';
            }
            // 🔹 Si todas están "Concluido"
            elseif ($estados->every(fn($e) => $e === 'Concluido')) {
                $estadoGeneral = 'Concluido';
            }
            // 🔹 Si todas están "Confirmado"
            elseif ($estados->every(fn($e) => $e === 'Confirmado')) {
                $estadoGeneral = 'Confirmado';
            }
            // 🔹 Si todas están "Publicado"
            elseif ($estados->every(fn($e) => $e === 'Publicado')) {
                $estadoGeneral = 'Publicado';
            }
            // 🔹 Si todas están "Cerrado"
            elseif ($estados->every(fn($e) => $e === 'Cerrado')) {
                $estadoGeneral = 'Cerrado';
            } else {
                $estadoGeneral = 'Mixto'; // Cuando hay una mezcla de estados
            }

            return [
                'id' => $area->id,
                'nombre' => $area->nombre_area,
                'estado' => $estadoGeneral,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    public function actualizarEstadoAreaNivelFase(Request $request, $idANF)
    {

        $nuevoEstado = $request->input('estado');
        $areaNivelFase = AreaNivelFase::find($idANF);
        if (!$areaNivelFase) {
            return response()->json([
                'status' => 'error',
                'message' => 'AreaNivelFase no encontrada.',
            ], 404);
        }
        if (!$nuevoEstado) {
            return response()->json([
                'status' => 'error',
                'message' => 'El nuevo estado es requerido.',
            ], 404);
        }

        if ($nuevoEstado == "En_revicion") {
            $evaluadorId = auth()->guard('sanctum')->user()->id;

            $persona = Persona::where('id_usuario', $evaluadorId);
            $asignacion = Asignacion::where('id_persona', $persona->id)
                ->where('id_area_nivel', $areaNivelFase->id_area_nivel)
                ->first();
            $rol = $persona->rols->first();

            if ($rol == "evaluador") {


                Evaluacion::where('id_asignacion', $asignacion->id)
                    ->update([
                        'estado_confirmacion' => 'confirmado'
                    ]);
                $todasConfirmadas = Evaluacion::where('id_asignacion', $asignacion->id)
                    ->where('estado_confirmacion', '!=', 'confirmado')
                    ->doesntExist();


                if ($todasConfirmadas) {
                    $areaNivelFase->update([
                        'estado' => 'En_revicion'
                    ]);

                }
                return response()->json([
                    'status' => 'success',
                    'message' => "Estado actualizado correctamente a '{$nuevoEstado}'.",
                ]);
            }
        } else if ($nuevoEstado == "publicado") {

            Evaluacion::whereHas('inscripcion.area_nivel.area_nivel_fase', function ($q) use ($idANF) {
                $q->where('id', $idANF);
            })
                ->update([
                    'estado_confirmacion' => 'publicado'
                ]);
            $areaNivelFase->update([
                'estado' => 'Publicado'
            ]);
            $this->estadoService->migrarEvaluacionesRanking($areaNivelFase->id);
            return response()->json([
                'status' => 'success',
                'message' => "Estado actualizado correctamente.",
            ]);
        }
    }

    public function actualizarEstados()
    {
        $hoy = now();

        // validar los estados de las fases
        $fases = Fase::all();
        $faseActiva = null;
        $armarFechaHora = function ($fecha, $hora) {
            if (!$fecha || !$hora)
                return null;

            // Normalizamos hora: quitamos segundos si los tiene
            $hora = substr($hora, 0, 5); // toma solo HH:MM

            return Carbon::createFromFormat(
                'Y-m-d H:i',
                Carbon::parse($fecha)->format('Y-m-d') . ' ' . $hora
            );
        };
        foreach ($fases as $fase) {

            $faseIniInicio = $armarFechaHora($fase->fecha_inicio, $fase->hora_inicio_ini);
            $faseFinFin = $armarFechaHora($fase->fecha_fin, $fase->hora_fin_fin);
            if (!$faseIniInicio || !$faseFinFin || $fase->estado === 'cerrada' || $fase->estado_publicado === 'borrador') {
                continue;
            }

            /*  Log::debug(
                 'fase',
                 array(
                     'fase' => $fase,
                     'faseIniInicio' => $faseIniInicio,
                     'faseFinFin' => $faseFinFin,
                     'hoy' => $hoy
                 )
             ); */
            if ($hoy->gt($faseIniInicio) && $hoy->lt($faseFinFin)) {
                if ($fase->estado === 'en proceso') {
                    $fase->estado = 'activa';
                    $fase->save();
                    if ($fase->nombre == "final") {
                        $this->estadoService->migrarEvaluaciones();
                    }
                }
                $faseActiva = $fase;
                Log::debug('fase_activa', array($faseActiva));
            } else if ($hoy->gt($faseFinFin)) {
                if ($fase->estado === 'activa') {
                    $fase->estado = 'cerrada';
                    $fase->save();
                    if ($fase->nombre = 'clasificación' || 'final') {
                        AreaNivelFase::where('id_fase', $fase->id)
                            ->update([
                                'estado' => 'concluido'
                            ]);
                    }

                }
                Log::debug('fase_cerrada', array($faseActiva));
            }
        }
        Log::debug('estado activo', [$faseActiva]);

        if (!$faseActiva) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay fases activas'
            ]);
        }

        $areaNivelFases = AreaNivelFase::where('id_fase', $faseActiva->id)->get();
        $actividades = Actividad::where('id_fase', $faseActiva->id)->get();
        if ($areaNivelFases->isEmpty() || $actividades->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay areaNivelFases o actividades'
            ]);
        }

        log::debug('areaNivelFases', [$areaNivelFases, $actividades]);
        $actividadActiva = null;
        foreach ($actividades as $actividad) {
            $actividadIniInicio = $armarFechaHora($actividad->fecha_inicio, $actividad->hora_inicio_ini);
            $actividadFinFin = $armarFechaHora($actividad->fecha_fin, $actividad->hora_fin_fin);

            if (!$actividadIniInicio || !$actividadFinFin) {
                continue;
            }

            if ($hoy->gt($actividadIniInicio) && $hoy->lt($actividadFinFin)) {
                if ($actividad->nombre === 'calificacion') {
                    $todoConfirmado = $areaNivelFases->every(function ($item) {
                        return $item->estado === 'En_evaluacion';
                    });
                    if (!$todoConfirmado) {
                        $areaNivelFases->each(function ($item) {

                            $item->estado = 'En_evaluacion';
                            $item->save();
                        });
                    }
                } else if ($actividad->nombre === 'revicion') {
                    $todoConfirmado = $areaNivelFases->every(function ($item) {
                        return $item->estado === 'En_revicion';
                    });
                    if (!$todoConfirmado) {
                        $areaNivelFases->each(function ($item) {
                            if ($item->estado != 'confirmado') {
                                $item->estado = 'En_revicion';
                                $item->save();
                            }
                        });
                        Evaluacion::where('id_fase', $faseActiva->id)
                            ->update([
                                'estado_confirmacion' => 'confirmado'
                            ]);
                    }
                } else if ($actividad->nombre === 'publicacion') {
                    $todoConfirmado = $areaNivelFases->every(function ($item) {
                        return $item->estado === 'confirmado';
                    });

                    if (!$todoConfirmado) {
                        Evaluacion::where('id_fase', $faseActiva->id)
                            ->update([
                                'estado_confirmacion' => 'aprobado'
                            ]);
                        $areaNivelFases->each(function ($item) {
                            if ($item->estado != 'confirmado') {
                                $this->estadoService->migrarEvaluacionesRanking($item->id);
                                $item->estado = 'confirmado';
                                $item->save();
                            }

                        });


                    }

                }
            } else if ($hoy->gt($actividadFinFin)) {
                if ($actividad->nombre === 'publicacion') {
                    $todoConfirmado = $areaNivelFases->every(function ($item) {
                        return $item->estado === 'publicado';
                    });
                    if (!$todoConfirmado) {

                        foreach ($areaNivelFases as $anf) {
                            if ($anf->estado === 'confirmado') {
                                $anf->estado = 'publicado';
                                $anf->save();
                            }

                        }
                        Evaluacion::where('id_fase', $faseActiva->id)
                            ->update([
                                'estado_confirmacion' => 'publicado'
                            ]);

                    }
                } else if ($actividad->nombre === 'calificacion') {
                    $todoConfirmado = $areaNivelFases->every(function ($item) {
                        return $item->estado === 'En_revicion';
                    });
                    if (!$todoConfirmado) {
                        $areaNivelFases->each(function ($item) {
                            $item->estado = 'En_revicion';
                            $item->save();
                        });
                        Evaluacion::where('id_fase', $faseActiva->id)
                            ->update([
                                'estado_confirmacion' => 'confirmado'
                            ]);
                    }
                } else if ($actividad->nombre === 'revicion') {
                    $todoConfirmado = $areaNivelFases->every(function ($item) {
                        return $item->estado === 'confirmado';
                    });
                    if (!$todoConfirmado) {
                        Evaluacion::where('id_fase', $faseActiva->id)
                            ->update([
                                'estado_confirmacion' => 'aprobado'
                            ]);
                        $areaNivelFases->each(function ($item) {
                            if ($item->estado != 'confirmado') {
                                $this->estadoService->migrarEvaluacionesRanking($item->id);
                                $item->estado = 'confirmado';
                                $item->save();
                            }
                        });

                    }
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'actualizacion de estados exitosa']);
    }
    public function actualizarEstadoAreaNivelFaseTodos()
    {
        $areasNivelFases = AreaNivelFase::all();
        $areasNivelFases->each(function ($item) {
            $item->estado = 'En_evaluacion';
            $item->save();
        });
        $faseActiva = Fase::where('estado', 'activa')->first();
        $this->estadoService->migrarEvaluacionesRanking();

        Evaluacion::where('id_fase', $faseActiva->id)
            ->update([
                'estado_confirmacion' => 'publicado'
            ]);

        return response()->json(['status' => 'success', 'message' => 'actualizacion de estados exitosa']);
    }

    /**
     * Summary of publicarFaseCompleta
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function publicarFaseCompleta($id)
    {
        $res = $this->estadoService->publicarResultadosTodas($id);
        return response()->json($res['content'], $res['status_code']);
    }
}


