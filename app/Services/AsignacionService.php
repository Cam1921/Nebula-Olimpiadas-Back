<?php

namespace App\Services;

use App\Models\AreaNivel;
use App\Models\Asignacion;
use App\Models\Evaluacion;
use App\Models\Fase;
use App\Models\Nivel;
use App\Models\Persona;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AsignacionService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    /**
     * Listar asignaciones de evaluadores
     * @param mixed $request
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function listarAsignaciones($request)
    {
        try {
            $id_area = $request->input('id_area');
            $id_nivel = $request->input('id_nivel');

            // Si no envían ambos filtros, devolver vacío
            if (!$id_area || !$id_nivel) {
                return [
                    'status_code' => 500,
                    'content' => [
                        'status' => 'success',
                        'data' => [],
                        'meta' => [
                            'limite_por_evaluador' => null,
                            'total_evaluadores' => 0
                        ]
                    ]
                ];
            }

            // Buscar fase ACTIVA
            $faseActiva = Fase::where('estado', 'activa')
                ->where(function ($q) {
                    $q->where('nombre', 'clasificacion')
                        ->orWhere('nombre', 'final');
                })
                ->first();

            if (!$faseActiva) {
                return [
                    'status_code' => 500,
                    'content' => [
                        'status' => 'success',
                        'data' => [],
                        'meta' => [
                            'limite_por_evaluador' => null,
                            'total_evaluadores' => 0
                        ],
                        'message' => 'No existe una fase activa.'
                    ]
                ];
            }

            // Buscar el area_nivel exacto
            $areaNivel = AreaNivel::where('id_area', $id_area)
                ->where('id_nivel', $id_nivel)
                ->first();

            if (!$areaNivel) {
                return [
                    'status_code' => 500,
                    'content' => [
                        'status' => 'success',
                        'data' => [],
                        'meta' => [
                            'limite_por_evaluador' => null,
                            'total_evaluadores' => 0
                        ]
                    ]
                ];
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

            $total_competidores = $this->obtenerTotalCompetidoresAreaNivel($areaNivel->id, $faseActiva->id);

            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'data' => $evaluadores,
                    'meta' => [
                        'limite_por_evaluador' => $limiteEvaluador,
                        'total_evaluadores' => $evaluadores->count(),
                        'limite_cantidad_evaluadores' => $areaNivel->nivel->cantidad_evaluadores,
                        'total_competidores' => $total_competidores
                    ],
                    'message' => 'Asignaciones obtenidas correctamente',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error interno del servidor al listar asignaciones.'
                ]
            ];

        }
    }

    private function obtenerTotalCompetidoresAreaNivel($idAreaNivel, $idFase)
    {
        $total = Evaluacion::where('id_fase', $idFase)
            ->whereHas('inscripcion', function ($q) use ($idAreaNivel) {
                $q->where('id_area_nivel', $idAreaNivel);
            })
            ->count();
        return $total;

    }
    /**
     * Asignar evaluadores a un area-nivel
     * @param array $evaluadores
     * @param int $idAreaNivel
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function asignarEvaluadores(array $evaluadores, int $idAreaNivel)
    {
        Log::debug('evaluadores', [$evaluadores]);

        $areaNivel = AreaNivel::find($idAreaNivel);
        if (!$areaNivel) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Area y nivel no encontrada'
                ]
            ];
        }

        $errores = [];
        $filaErrores = [];

        foreach ($evaluadores as $evaluador) {
            $persona = Persona::find($evaluador);

            if (!$persona) {

                $filaErrores[] = [
                    'id_evaluador' => $evaluador,
                    'error' => "No se encuentra un evaluador con el id '{$evaluador}'"
                ];
                continue;
            }


            Asignacion::create([
                'id_persona' => $evaluador,
                'id_area_nivel' => $idAreaNivel
            ]);
        }


        $errores = array_merge($errores, $filaErrores);

        return [
            'status_code' => 201,
            'content' => [
                'status' => 'success',
                'message' => 'Asignaciones creadas correctamente',
                'errors' => $errores
            ]
        ];
    }

    /**
     * Eliminar evaluadores de un area-nivel
     * @param array $evaluadores
     * @param int $idAreaNivel
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function eliminarEvaluadores(array $evaluadores, int $idAreaNivel)
    {
        Log::debug('evaluadores_a_eliminar', [$evaluadores]);

        // Verificar que existe el área/nivel
        $areaNivel = AreaNivel::find($idAreaNivel);
        if (!$areaNivel) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Area y nivel no encontrada'
                ]
            ];
        }

        $errores = [];
        $filaErrores = [];

        foreach ($evaluadores as $evaluador) {

            // 1. Verificar si existe la persona/evaluador
            $persona = Persona::find($evaluador);
            if (!$persona) {
                $filaErrores[] = [
                    'id_evaluador' => $evaluador,
                    'error' => "No se encuentra un evaluador con el id '{$evaluador}'"
                ];
                continue;
            }

            // 2. Buscar asignación para ese evaluador EN ESE área/nivel
            $asignacion = Asignacion::where('id_persona', $evaluador)
                ->where('id_area_nivel', $idAreaNivel)
                ->first();

            if (!$asignacion) {
                $filaErrores[] = [
                    'id_evaluador' => $evaluador,
                    'error' => "El evaluador '{$evaluador}' no tiene una asignación en esta área/nivel"
                ];
                continue;
            }

            // 3. Eliminar asignación
            $asignacion->delete();
        }

        // Combinar errores
        $errores = array_merge($errores, $filaErrores);

        return [
            'status_code' => 200,
            'content' => [
                'status' => 'success',
                'message' => 'Asignaciones eliminadas correctamente',
                'errors' => $errores
            ]
        ];
    }
    /**
     * Asignar inscritos a evaluadores
     * @param mixed $request
     * @return array{content: array, status_code: int|array{content: array{evaluadores_usados: mixed, message: string, status: string, total_asignados: mixed}, status_code: int}|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function asignarCompetidores($request)
    {
        try {
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
                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'message' => 'No existe una fase activa o no corresponde a las fases de clasificación o final.'
                    ]
                ];
            }

            // ===============================
            // 1. GUARDAR LÍMITES EN NIVEL
            // ===============================
            $nivel = Nivel::find($idNivel);

            if (!$nivel) {
                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'message' => 'El nivel especificado no existe.'
                    ]
                ];
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

                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'message' => 'El área y nivel especificados no existen.'
                    ]
                ];

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

                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'message' => 'No existen evaluadores para este área y nivel.'
                    ]
                ];
            }

            // ===============================
            // 4. FILTRAR SOLO LA CANTIDAD SOLICITADA   (NUEVO)
            // ===============================
            if ($evaluadores->count() < $cantidadEvaluadores) {
                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'message' => 'No hay suficientes evaluadores registrados para cumplir la cantidad requerida.'

                    ]
                ];
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
                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'message' => 'No existen inscritos para asignar en este área y nivel.'

                    ]
                ];
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

            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Asignación realizada correctamente.',
                    'total_asignados' => $evaluaciones->count(),
                    'evaluadores_usados' => $cantidadEvaluadores
                ]
            ];

        } catch (ValidationException $e) {
            return [
                'status_code' => 422,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error interno del servidor al asignar competidores.'
                ]
            ];
        }



    }

}
