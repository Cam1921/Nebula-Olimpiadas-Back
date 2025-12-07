<?php

namespace App\Services;

use App\Repositories\ActividadRepository;
use App\Repositories\FaseRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;


/**
 * Summary of ActividadService
 */
class ActividadService
{
    protected $actividadRepository;
    protected $faseRepository;

    /**
     * Summary of __construct
     * @param ActividadRepository $actividadRepository
     * @param FaseRepository $faseRepository
     */
    public function __construct(ActividadRepository $actividadRepository, FaseRepository $faseRepository)
    {
        $this->actividadRepository = $actividadRepository;
        $this->faseRepository = $faseRepository;
    }


    /**
     * Summary of listarActividades
     * @return array{content: array{data: \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection, message: string, status: string, status_code: int}|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function listarActividades()
    {

        try {
            $actividades = $this->actividadRepository->all();

            $actividadesFiltradas = $actividades->map(function ($actividad) {
                return [
                    'id' => $actividad->id,
                    'nombre' => $actividad->nombre,
                    'descripcion' => $actividad->descripcion,
                    'fecha_inicio' => $actividad->fecha_inicio,
                    'hora_inicio_ini' => $actividad->hora_inicio_ini,
                    'hora_fin_ini' => $actividad->hora_fin_ini,
                    'fecha_fin' => $actividad->fecha_fin,
                    'hora_inicio_fin' => $actividad->hora_inicio_fin,
                    'hora_fin_fin' => $actividad->hora_fin_fin,
                    'fase' => $actividad->fase ? $actividad->fase->nombre : null,
                    'fase_id' => $actividad->fase ? $actividad->fase->id : null,
                    'estado_publicado' => $actividad->estado_publicado,
                ];
            });
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Lista de actividades obtenida correctamente',
                    'data' => $actividadesFiltradas
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al obtener las actividades' . $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Summary of obtenerActividad
     * @param mixed $id
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function obtenerActividad($id)
    {
        try {
            $actividad = $this->actividadRepository->findById($id);

            if (!$actividad) {
                return [
                    'status_code' => 404,
                    'content' => [
                        'status' => 'error',
                        'message' => 'Actividad no encontrada'
                    ]
                ];
            }

            $data = [
                'id' => $actividad->id,
                'nombre' => $actividad->nombre,
                'descripcion' => $actividad->descripcion,
                'fecha_inicio' => $actividad->fecha_inicio,
                'hora_inicio_ini' => $actividad->hora_inicio_ini,
                'hora_fin_ini' => $actividad->hora_fin_ini,
                'fecha_fin' => $actividad->fecha_fin,
                'hora_inicio_fin' => $actividad->hora_inicio_fin,
                'hora_fin_fin' => $actividad->hora_fin_fin,
                'fase' => $actividad->fase ? $actividad->fase->nombre : null,
                'fase_id' => $actividad->fase ? $actividad->fase->id : null,
                'estado_publicado' => $actividad->estado_publicado,
            ];

            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Actividad obtenida correctamente',
                    'data' => $data
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al obtener  la actividad:' . $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Summary of actualizarActividad
     * @param mixed $id
     * @param array $data
     * @return array{content: array{data: \App\Models\Actividad|\Illuminate\Database\Eloquent\Builder, message: string, status: string, status_code: int}|array{content: array{errors: \Illuminate\Support\MessageBag, status: string}, status_code: int}|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function actualizarActividad($id, array $data)
    {
        try {
            $actividad = $this->actividadRepository->findById($id);

            if (!$actividad) {
                return [
                    'status_code' => 404,
                    'content' => [
                        'status' => 'error',
                        'message' => 'Actividad no encontrada'
                    ]
                ];
            }

            $fase = $actividad->fase;
            // Validación: no permitir fechas si la fase no tiene fechas
            if (
                ($fase->fecha_inicio === null || $fase->fecha_fin === null)
                && (isset($data['fecha_inicio']) || isset($data['fecha_fin']))
            ) {
                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'message' => 'No se pueden asignar fechas a la actividad mientras la fase no tenga fechas definidas.'
                    ]
                ];
            }

            // Validaciones nueva estructura
            $validator = Validator::make($data, [
                'nombre' => 'sometimes|required|string|max:255',
                'descripcion' => 'nullable|string',
                'estado_publicado' => 'sometimes|required|in:sin_fechas,publicado,borrador',
                'fecha_inicio' => 'sometimes|nullable|date',
                'hora_inicio_ini' => 'sometimes|nullable|date_format:H:i',
                'hora_fin_ini' => 'sometimes|nullable|date_format:H:i|after:hora_inicio_ini',

                'fecha_fin' => 'sometimes|nullable|date',
                'hora_inicio_fin' => 'sometimes|nullable|date_format:H:i',
                'hora_fin_fin' => 'sometimes|nullable|date_format:H:i|after:hora_inicio_fin',
            ]);

            if ($validator->fails()) {
                return [
                    'status_code' => 422,
                    'content' => [
                        'status' => 'error',
                        'errors' => $validator->errors()
                    ]
                ];
            }

            $validated = $validator->validated();

            //Armar rangos de tiempo
            $armarFechaHora = function ($fecha, $hora) {
                if (!$fecha || !$hora)
                    return null;

                // Normalizamos hora: QUITAMOS segundos si los tiene
                $hora = substr($hora, 0, 5); // toma solo HH:MM

                return Carbon::createFromFormat(
                    'Y-m-d H:i',
                    Carbon::parse($fecha)->format('Y-m-d') . ' ' . $hora
                );
            };

            // Actividad
            $iniInicio = $armarFechaHora($validated['fecha_inicio'] ?? null, $validated['hora_inicio_ini'] ?? null);
            $iniFin = $armarFechaHora($validated['fecha_inicio'] ?? null, $validated['hora_fin_ini'] ?? null);
            $finInicio = $armarFechaHora($validated['fecha_fin'] ?? null, $validated['hora_inicio_fin'] ?? null);
            $finFin = $armarFechaHora($validated['fecha_fin'] ?? null, $validated['hora_fin_fin'] ?? null);

            // Aase
            $faseIniInicio = $armarFechaHora($fase->fecha_inicio, $fase->hora_inicio_ini);
            $faseIniFin = $armarFechaHora($fase->fecha_inicio, $fase->hora_fin_ini);
            $faseFinInicio = $armarFechaHora($fase->fecha_fin, $fase->hora_inicio_fin);
            $faseFinFin = $armarFechaHora($fase->fecha_fin, $fase->hora_fin_fin);

            //Validar dentro de la fase 
            if ($iniInicio && $iniInicio->lt($faseIniInicio)) {
                return [
                    'status_code' => 400,
                    'content' => ['status' => 'error', 'message' => 'El inicio de la actividad es menor al inicio de la fase.']
                ];
            }




            if ($finFin && $finFin->gt($faseFinFin)) {
                return [
                    'status_code' => 400,
                    'content' => ['status' => 'error', 'message' => 'La fecha final de la actividad supera el límite de la fase.']
                ];
            }

            //Validar orden según actividades 
            $orden_c = ['asignar' => 1, 'calificar_c' => 2, 'publicacion_c' => 3];
            $orden_f = ['calificacion_f' => 1, 'publicacion_f' => 2, 'premiacion' => 3];

            $nombreActividad = strtolower($actividad->nombre);
            $faseNombre = strtolower($fase->nombre);
            $orden = null;

            if (in_array($faseNombre, ['clasificación', 'clasificacion'])) {
                $orden = $orden_c;
            } elseif ($faseNombre === 'final') {
                $orden = $orden_f;
            }

            if ($orden) {
                foreach ($fase->actividads as $otra) {
                    if ($otra->id === $actividad->id)
                        continue;

                    $nombreOtra = strtolower($otra->nombre);
                    if (!isset($orden[$nombreOtra]) || !isset($orden[$nombreActividad]))
                        continue;

                    $otraIniFin = $armarFechaHora($otra->fecha_inicio, $otra->hora_fin_ini);
                    $otraFinInicio = $armarFechaHora($otra->fecha_fin, $otra->hora_inicio_fin);

                    if ($orden[$nombreOtra] < $orden[$nombreActividad] && $iniInicio && $otraIniFin && $iniInicio->lt($otraIniFin)) {
                        return [
                            'status_code' => 400,
                            'content' => [
                                'status' => 'error',
                                'message' => "La actividad '{$actividad->nombre}' se solapa con la actividad anterior '{$otra->nombre}'."
                            ]
                        ];
                    }

                    if ($orden[$nombreOtra] > $orden[$nombreActividad] && $iniFin && $otraFinInicio && $iniFin->gt($otraFinInicio)) {
                        return [
                            'status_code' => 400,
                            'content' => [
                                'status' => 'error',
                                'message' => "La actividad '{$actividad->nombre}' se solapa con la siguiente actividad '{$otra->nombre}'."
                            ]
                        ];
                    }
                }
            }

            //Actualizar
            $actividad = $this->actividadRepository->update($id, $validated);

            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Actividad actualizada correctamente',
                    'data' => $actividad
                ]
            ];
        } catch (ValidationException $e) {

            return [
                'status_code' => 422,
                'content' => [
                    'status' => 'error',
                    'message' => "Error al actualizar la validacion.",
                    'errors' => $e->errors()
                ]
            ];
        } catch (\Exception $e) {

            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => "Error al actualizar la validacion." . $e->getMessage(),

                ]
            ];
        }
    }



    /**
     * Summary of verificarActividadPorNombreYFase
     * @param mixed $nombreActividad
     * @param mixed $nombreFase
     * @return array{content: array{activo: bool, data: mixed, message: string, status: string, status_code: int}|array{content: array{activo: bool, message: string, status: string}, status_code: int}|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function verificarActividadPorNombreYFase($nombreActividad, $nombreFase)
    {
        // Buscar la actividad filtrando también por la fase
        $actividad = $this->actividadRepository->findByNombreYFase($nombreActividad, $nombreFase);
        Log::debug('actividad_encontrada', [$actividad]);

        if (!$actividad) {
            return [
                'status_code' => 404,
                'content' => [
                    'status' => 'error',
                    'message' => 'Actividad no encontrada para la fase especificada'
                ]
            ];
        }

        $fase = $actividad->fase;

        if (!$fase) {
            return [
                'status_code' => 400,
                'content' => [
                    'status' => 'error',
                    'activo' => false,
                    'message' => 'La actividad no tiene fase asignada.'
                ]
            ];
        }

        // Función reutilizable de actualizarActividad para armar datetime
        $armarFechaHora = function ($fecha, $hora) {
            if (!$fecha || !$hora)
                return null;

            $hora = substr($hora, 0, 5); // HH:MM
            return Carbon::createFromFormat(
                'Y-m-d H:i',
                Carbon::parse($fecha)->format('Y-m-d') . ' ' . $hora
            );
        };

        // Rangos de la fase
        $inicioFase = $armarFechaHora($fase->fecha_inicio, $fase->hora_inicio_ini);
        $finFase = $armarFechaHora($fase->fecha_fin, $fase->hora_fin_fin);
        $now = now()->setTimezone(config('app.timezone'));

        Log::debug('Fase Rango', [
            'inicioFase' => $inicioFase,
            'finFase' => $finFase,
            'now' => $now,
        ]);

        // Si la fase no tiene fechas completas o no está activa
        if (!$inicioFase || !$finFase || !$now->between($inicioFase, $finFase)) {
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'activo' => false,
                    'message' => "La fase '{$fase->nombre}' no está activa actualmente."
                ]
            ];
        }

        // Rangos de la actividad
        $inicioAct = $armarFechaHora($actividad->fecha_inicio, $actividad->hora_inicio_ini);
        $finAct = $armarFechaHora($actividad->fecha_fin, $actividad->hora_fin_fin);

        if (!$inicioAct || !$finAct) {
            return [
                'status_code' => 400,
                'content' => [
                    'status' => 'error',
                    'activo' => false,
                    'message' => 'La actividad no tiene fechas definidas'
                ]
            ];
        }

        $activo = $now->between($inicioAct, $finAct);

        return [
            'status_code' => 200,
            'content' => [
                'status' => 'success',
                'activo' => $activo,
                'message' => $activo
                    ? 'La actividad está activa.'
                    : 'La actividad no está activa actualmente.',
                'data' => $actividad
            ]
        ];
    }


    /**
     * Summary of getActividadesPorFase
     * @param mixed $faseId
     * @return array{content: array, status_code: int|array{content: array{data: mixed, message: string, status: string}, status_code: int}|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function getActividadesPorFase($faseId)
    {
        try {
            $fase = $this->faseRepository->findById($faseId);
            if (!$fase) {
                return [
                    'status_code' => 400,
                    'content' => [
                        'status' => 'error',
                        'data' => [],
                        'message' => 'No hay actividades disponibles'
                    ]
                ];
            }
            $data = $this->actividadRepository->getByFaseId($faseId);
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'data' => $data,
                    'message' => 'Actividades obtenidas correctamente por fase',

                ]
            ];
        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al obtener las actividades por fase: ' . $e->getMessage(),
                ],
            ];

        }
    }


}
