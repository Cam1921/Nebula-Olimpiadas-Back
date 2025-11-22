<?php

namespace App\Services;

use App\Repositories\ActividadRepository;
use App\Repositories\FaseRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ActividadService
{
    protected $actividadRepository;
    protected $faseRepository;
    public function __construct(ActividadRepository $actividadRepository, FaseRepository $faseRepository)
    {
        $this->actividadRepository = $actividadRepository;
        $this->faseRepository = $faseRepository;
    }

    public function listarActividades()
    {
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
        return $actividadesFiltradas;
    }
    public function obtenerActividad($id)
    {
        return $this->actividadRepository->findById($id);
    }

    public function actualizarActividad($id, array $data)
    {
        $actividad = $this->actividadRepository->findById($id);

        if (!$actividad) {
            return [
                'status_code' => 404,
                'content' => [
                    'state' => 'error',
                    'message' => 'Actividad no encontrada'
                ]
            ];
        }
        Log::debug('data', $data);
        $fase = $actividad->fase;
        Log::debug('fase_actividad', array($fase));
        // Validación: no permitir fechas si la fase no tiene fechas
        if (
            ($fase->fecha_inicio === null || $fase->fecha_fin === null)
            && (isset($data['fecha_inicio']) || isset($data['fecha_fin']))
        ) {
            return [
                'status_code' => 400,
                'content' => [
                    'state' => 'error',
                    'message' => 'No se pueden asignar fechas a la actividad mientras la fase no tenga fechas definidas.'
                ]
            ];
        }

        // VALIDACIONES NUEVA ESTRUCTURA
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
                    'state' => 'error',
                    'errors' => $validator->errors()
                ]
            ];
        }

        $validated = $validator->validated();
        log::debug('validated', $validated);

        // *************** ARMAR RANGOS DE TIEMPO ***************
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

        // ACTIVIDAD
        $iniInicio = $armarFechaHora($validated['fecha_inicio'] ?? null, $validated['hora_inicio_ini'] ?? null);
        $iniFin = $armarFechaHora($validated['fecha_inicio'] ?? null, $validated['hora_fin_ini'] ?? null);
        $finInicio = $armarFechaHora($validated['fecha_fin'] ?? null, $validated['hora_inicio_fin'] ?? null);
        $finFin = $armarFechaHora($validated['fecha_fin'] ?? null, $validated['hora_fin_fin'] ?? null);
        Log::debug('Actividad Rango', [
            'iniInicio' => $iniInicio,
            'iniFin' => $iniFin,
            'finInicio' => $finInicio,
            'finFin' => $finFin,
        ]);
        // FASE
        $faseIniInicio = $armarFechaHora($fase->fecha_inicio, $fase->hora_inicio_ini);
        $faseIniFin = $armarFechaHora($fase->fecha_inicio, $fase->hora_fin_ini);
        $faseFinInicio = $armarFechaHora($fase->fecha_fin, $fase->hora_inicio_fin);
        $faseFinFin = $armarFechaHora($fase->fecha_fin, $fase->hora_fin_fin);
        log::debug('Fase Rango', [
            'faseIniInicio' => $faseIniInicio,
            'faseIniFin' => $faseIniFin,
            'faseFinInicio' => $faseFinInicio,
            'faseFinFin' => $faseFinFin,
        ]);
        // *************** VALIDAR DENTRO DE LA FASE ***************
        if ($iniInicio && $iniInicio->lt($faseIniInicio)) {
            return [
                'status_code' => 400,
                'content' => ['state' => 'error', 'message' => 'El inicio de la actividad es menor al inicio de la fase.']
            ];
        }




        if ($finFin && $finFin->gt($faseFinFin)) {
            return [
                'status_code' => 400,
                'content' => ['state' => 'error', 'message' => 'El fin final de la actividad supera el límite de la fase.']
            ];
        }

        // *************** VALIDAR ORDEN SEGÚN ACTIVIDADES ***************
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
                            'state' => 'error',
                            'message' => "La actividad '{$actividad->nombre}' se solapa con la actividad anterior '{$otra->nombre}'."
                        ]
                    ];
                }

                if ($orden[$nombreOtra] > $orden[$nombreActividad] && $iniFin && $otraFinInicio && $iniFin->gt($otraFinInicio)) {
                    return [
                        'status_code' => 400,
                        'content' => [
                            'state' => 'error',
                            'message' => "La actividad '{$actividad->nombre}' se solapa con la siguiente actividad '{$otra->nombre}'."
                        ]
                    ];
                }
            }
        }

        // *************** ACTUALIZAR ***************
        $actividad = $this->actividadRepository->update($id, $validated);

        return [
            'status_code' => 200,
            'content' => [
                'state' => 'success',
                'message' => 'Actividad actualizada correctamente',
                'data' => $actividad
            ]
        ];
    }



    public function verificarActividadPorNombreYFase($nombreActividad, $nombreFase)
    {
        // Buscar la actividad filtrando también por la fase
        $actividad = $this->actividadRepository->findByNombreYFase($nombreActividad, $nombreFase);
        log::debug('actividad_encontrada', array($actividad));
        if (!$actividad) {
            return response()->json([
                'state' => 'error',
                'message' => 'Actividad no encontrada para la fase especificada'
            ], 404);
        }

        $fase = $actividad->fase;

        if (!$fase) {
            return response()->json([
                'state' => 'error',
                'activo' => false,
                'message' => 'La actividad no tiene fase asignada.'
            ], 400);
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

        log::debug('Fase Rango', [
            'inicioFase' => $inicioFase,
            'finFase' => $finFase,
            'now' => $now,
        ]);
        // Si la fase no tiene fechas completas o no está activa
        if (!$inicioFase || !$finFase || !$now->between($inicioFase, $finFase)) {
            return response()->json([
                'state' => 'success',
                'activo' => false,
                'message' => "La fase '{$fase->nombre}' no está activa actualmente."
            ]);
        }

        // Rangos de la actividad
        $inicioAct = $armarFechaHora($actividad->fecha_inicio, $actividad->hora_inicio_ini);
        $finAct = $armarFechaHora($actividad->fecha_fin, $actividad->hora_fin_fin);

        log::debug('Actividad Rango', [
            'inicioAct' => $inicioAct,
            'finAct' => $finAct,
        ]);
        // Si la actividad no tiene fechas completas, se activa mientras su fase esté activa
        if (!$inicioAct || !$finAct) {
            return response()->json([
                'state' => 'success',
                'activo' => true,
                'message' => 'La actividad está disponible mientras la fase esté activa.'
            ]);
        }

        $activo = $now->between($inicioAct, $finAct);

        return response()->json([
            'state' => 'success',
            'activo' => $activo,
            'message' => $activo
                ? 'La actividad está activa.'
                : 'La actividad no está activa actualmente.'
        ]);
    }


    public function getActividadesPorFase($faseId)
    {
        $fase = $this->faseRepository->findById($faseId);
        if (!$fase) {
            return null; // o lanzar excepción
        }
        return $this->actividadRepository->getByFaseId($faseId);
    }


}
