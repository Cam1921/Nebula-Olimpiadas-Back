<?php

namespace App\Services;

use App\Repositories\FaseRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Fase;
use Carbon\Carbon;


class FaseService
{
    protected $faseRepository;

    public function __construct(FaseRepository $faseRepository)
    {
        $this->faseRepository = $faseRepository;
    }


    /**
     * Lista fases con paginación y búsqueda opcional
     * @param mixed $busqueda
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function listar(?string $busqueda = null, int $perPage = 10)
    {
        return $this->faseRepository->paginate($busqueda, $perPage);
    }

    /**
     * Lista todas las fases
     * @throws \Exception
     * @return \Illuminate\Database\Eloquent\Collection<int, Fase>
     */
    public function listarFases()
    {
        $fases = $this->faseRepository->all();
        if (!$fases) {
            throw new \Exception('No se encontraron fases.');
        }
        return $fases;
    }
    /**
     * Obener fases
     */
    public function obtenerFases()
    {
        return $this->faseRepository->getFases();
    }

    /**
     * Crear una nueva fase
     * @param array $data
     * @throws ValidationException
     * @return Fase
     */
    public function crear(array $data): Fase
    {
        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:abierto,cerrado',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Si se abre una nueva fase, cerrar las anteriores
        if ($data['estado'] === 'abierto') {
            $this->faseRepository->cerrarFasesActivas();
        }

        return $this->faseRepository->create($validator->validated());
    }


    /**
     * Actualiza una fase
     * @param mixed $id
     * @param array $data
     * @return array{content: array{data: Fase|null, message: string, state: string, status_code: int}|array{content: array{errors: \Illuminate\Support\MessageBag, state: string}, status_code: int}|array{content: array{message: string, state: string}, status_code: int}}
     */
    public function actualizarFase($id, array $data)
    {
        $fase = Fase::find($id);
        $todasLasFases = Fase::All();

        if (!$fase) {
            return [
                'status_code' => 404,
                'content' => [
                    'state' => 'error',
                    'message' => 'Fase no encontrada'
                ]
            ];
        }

        Log::debug('data', $data);

        // VALIDACIÓN
        $validator = Validator::make($data, [
            'mode' => 'required|in:create,edit',
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'estado_publicado' => 'sometimes|required|in:sin_fechas,borrador,publicado',

            'fecha_inicio' => 'sometimes|nullable|date',
            'hora_inicio_ini' => 'sometimes|nullable|date_format:H:i',
            'hora_fin_ini' => 'sometimes|nullable|date_format:H:i|after_or_equal:hora_inicio_ini',

            'fecha_fin' => 'sometimes|nullable|date',
            'hora_inicio_fin' => 'sometimes|nullable|date_format:H:i',
            'hora_fin_fin' => 'sometimes|nullable|date_format:H:i|after_or_equal:hora_inicio_fin',
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
        Log::debug('validated', $validated);

        $mode = $validated['mode'];
        if ($mode === "create") {
            if ($fase->estado_publicado === "borrador" || $fase->estado_publicado === "publicado") {
                return [
                    'status_code' => 400,
                    'content' => [
                        'state' => 'error',
                        'message' => 'Esta fase ya está registrada en el cronograma.'
                    ]
                ];
            }
        }

        // *************** ARMAR RANGOS DE TIEMPO ***************
        $armarFechaHora = function ($fecha, $hora) {
            if (!$fecha || !$hora)
                return null;
            $hora = substr($hora, 0, 5); // HH:MM
            return Carbon::createFromFormat(
                'Y-m-d H:i',
                Carbon::parse($fecha)->format('Y-m-d') . ' ' . $hora
            );
        };

        $inicioSim = $armarFechaHora($validated['fecha_inicio'] ?? null, $validated['hora_inicio_ini'] ?? null);
        $finSim = $armarFechaHora($validated['fecha_fin'] ?? null, $validated['hora_fin_fin'] ?? null);



        // *************** VALIDAR CONSISTENCIA INTERNA ***************
        if ($inicioSim && $finSim && $inicioSim->gt($finSim)) {
            return [
                'status_code' => 400,
                'content' => [
                    'state' => 'error',
                    'message' => 'La fase no puede terminar antes de la fecha y hora en que inicia.'
                ]
            ];
        }

        // *************** VALIDAR COLISIONES ENTRE FASES ***************
        $orden = [
            'inscripcion' => 1,
            'clasificacion' => 2,
            'final' => 3,
        ];
        $nombreActual = strtolower($fase->nombre);

        if (!isset($orden[$nombreActual])) {
            return [
                'status_code' => 400,
                'content' => [
                    'state' => 'error',
                    'message' => "La fase '{$fase->nombre}' no tiene un orden definido en el sistema."
                ]
            ];
        }

        if ($inicioSim && $finSim) {
            foreach ($todasLasFases as $otraFase) {
                if ($otraFase->id === $fase->id)
                    continue;

                $nombreOtra = strtolower($otraFase->nombre);
                if (!isset($orden[$nombreOtra]))
                    continue;
                Log::debug("orden de la comparacion", [$orden[$nombreActual], $orden[$nombreOtra]]);
                Log::debug("otra fase", [$otraFase]);
                if (!$otraFase->fecha_inicio || !$otraFase->hora_inicio_ini || !$otraFase->fecha_fin || !$otraFase->hora_fin_fin)
                    continue;

                $inicioOtra = $armarFechaHora($otraFase->fecha_inicio, $otraFase->hora_inicio_ini);
                $finOtra = $armarFechaHora($otraFase->fecha_fin, $otraFase->hora_fin_fin);
                Log::debug("comparacion", [$inicioOtra, $finOtra, $inicioSim, $finSim]);

                if ($orden[$nombreOtra] < $orden[$nombreActual] && $inicioSim->lt($finOtra)) {
                    return [
                        'status_code' => 400,
                        'content' => [
                            'state' => 'error',
                            'message' => "La fase '{$fase->nombre}' empieza antes de que finalice la fase anterior '{$otraFase->nombre}'."
                        ]
                    ];
                }

                if ($orden[$nombreOtra] > $orden[$nombreActual] && $finSim->gt($inicioOtra)) {
                    return [
                        'status_code' => 400,
                        'content' => [
                            'state' => 'error',
                            'message' => "La fase '{$fase->nombre}' termina después de que inicia la siguiente fase '{$otraFase->nombre}'."
                        ]
                    ];
                }
            }
        }

        // *************** ACTUALIZAR ***************
        $updated = $this->faseRepository->update($id, $validated);

        return [
            'status_code' => 200,
            'content' => [
                'state' => 'success',
                'message' => 'Fase actualizada correctamente',
                'data' => $updated
            ]
        ];
    }



    /**
     * Obtener una fase por id
     * @param mixed $id
     * @return Fase|null
     */
    public function obtenerFase($id)
    {
        return $this->faseRepository->findById($id);
    }

    /**
     * Eliminar una fase
     * @param int $id
     * @throws \Exception
     * @return bool
     */
    public function eliminar(int $id): bool
    {
        $fase = $this->faseRepository->findById($id);
        if (!$fase) {
            throw new \Exception('Fase no encontrada.');
        }

        return $this->faseRepository->delete($fase);
    }

    /**
     * Verifica el estado de una fase por nombre
     * @param mixed $nombreFase
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarEstado($nombreFase)
    {
        $fase = $this->faseRepository->findNombre($nombreFase);

        if (!$fase) {
            return response()->json([
                'state' => 'error',
                'message' => 'Fase no encontrada'
            ], 404);
        }

        // Validar que tenga fechas y horas completas
        if (
            !$fase->fecha_inicio || !$fase->hora_inicio_ini ||
            !$fase->fecha_fin || !$fase->hora_fin_fin
        ) {
            return response()->json([
                'state' => 'error',
                'activo' => false,
                'message' => 'La fase aún no tiene fechas y horas completas definidas.'
            ], 400);
        }

        // Función reutilizable de actualizarFase para armar datetime
        $armarFechaHora = function ($fecha, $hora) {
            if (!$fecha || !$hora)
                return null;
            $hora = substr($hora, 0, 5); // solo HH:MM
            return Carbon::createFromFormat(
                'Y-m-d H:i',
                Carbon::parse($fecha)->format('Y-m-d') . ' ' . $hora
            );
        };

        $inicio = $armarFechaHora($fase->fecha_inicio, $fase->hora_inicio_ini);
        $fin = $armarFechaHora($fase->fecha_fin, $fase->hora_fin_fin);

        if (!$inicio || !$fin) {
            return response()->json([
                'state' => 'error',
                'activo' => false,
                'message' => 'No se pudo construir correctamente el rango de la fase.'
            ], 400);
        }

        $now = now();

        if ($now->between($inicio, $fin)) {
            return response()->json([
                'state' => 'success',
                'activo' => true,
                'message' => "La fase '{$fase->nombre}' está activa."
            ]);
        }

        return response()->json([
            'state' => 'success',
            'activo' => false,
            'message' => "La fase '{$fase->nombre}' no está activa actualmente."
        ]);
    }


}
