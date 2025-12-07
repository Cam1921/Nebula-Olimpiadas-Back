<?php

namespace App\Services;

use App\Models\AreaNivelFase;
use App\Models\Competidor;
use App\Models\Equipo;
use App\Models\Evaluacion;
use App\Models\Fase;
use App\Models\Persona;
use App\Models\Ranking;
use App\Repositories\AreaNivelFaseRepository;
use App\Repositories\EvaluacionRepository;
use App\Traits\ApiResponseTrait;
use DB;
use Dom\Notation;
use Illuminate\Support\Facades\Log;


class EvaluacionesService
{
    use ApiResponseTrait;

    protected $evaluacionRepository;


    protected $areaNivelFaseRepo;
    protected $areaNivelService;
    protected $estadoService;

    public function __construct(AreaNivelFaseRepository $areaNivelFaseRepo, AreaNivelService $areaNivelService, EstadosServide $estadoService, EvaluacionRepository $evaluacionRepository)
    {
        $this->areaNivelFaseRepo = $areaNivelFaseRepo;
        $this->areaNivelService = $areaNivelService;
        $this->evaluacionRepository = $evaluacionRepository;
        $this->estadoService = $estadoService;
    }

    public function obtenerEvaluaciones($idAreaNivelFase, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion)
    {
        try {
            $idPersona = auth()->guard('sanctum')->user()->personas()->first()->id;

            $areaNivelFase = AreaNivelFase::find($idAreaNivelFase);
            if (!$areaNivelFase) {
                return [
                    'status_code' => 404,
                    'content' => [
                        'status' => 'error',
                        'message' => 'No se encontró el área, nivel o fase'
                    ]
                ];
            }
            $area = $areaNivelFase->area_nivel->area;
            if ($area->es_grupal) {
                $evaluaciones = $this->obtenerEvaluacionesEquipo($idPersona, $idAreaNivelFase, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion);
            } else {
                $evaluaciones = $this->obtenerEvaluacionesPorEvaluador($idPersona, $idAreaNivelFase, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion);
            }
            $data = $evaluaciones->items();
            $meta = [
                'current_page' => $evaluaciones->currentPage(),
                'per_page' => $evaluaciones->perPage(),
                'total' => $evaluaciones->total(),
                'last_page' => $evaluaciones->lastPage(),
                'next_page_url' => $evaluaciones->nextPageUrl(),
                'prev_page_url' => $evaluaciones->previousPageUrl(),
                'links' => $evaluaciones->linkCollection(),
            ];
            return [
                'status_code' => 200,
                'content' => [
                    'message' => 'Evaluaciones obtenidas correctamente.',
                    'data' => $data,
                    'meta' => $meta
                ]

            ];

        } catch (\Exception $e) {

            $code = str_contains($e->getMessage(), 'No se encontraron') ? 404 : 500;
            return [
                'status_code' => $code,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al obtener las evaluaciones por fase: ' . $e->getMessage(),
                ],
            ];
        }

    }
    public function obtenerEvaluacionesEquipo($idPersona, $idAreaNivelFase, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion)
    {
        $persona = Persona::with('rols')->find($idPersona);
        $esEvaluadoor = $persona->rols->contains('nombre', 'evaluador');
        $fase = AreaNivelFase::with('fase')->find($idAreaNivelFase);
        $query = Evaluacion::with([
            'fase',
            'inscripcion.competidor',
            'inscripcion.area_nivel.area',
            'inscripcion.area_nivel.nivel',
            'inscripcion.competidor.equipo',
        ])
            ->whereHas('inscripcion.area_nivel.asignacions.persona.rols', function ($q) {
                $q->where('nombre', 'evaluador');
            })
            ->whereHas('inscripcion.area_nivel.area_nivel_fase', function ($q) use ($idAreaNivelFase) {
                $q->where('id', $idAreaNivelFase);
            });
        if ($esEvaluadoor) {
            $query->whereHas('asignacion', function ($q) use ($idPersona) {
                $q->where('id_persona', $idPersona);
            });
            Log::debug('esta entrando');
        } else {
            $query->whereHas('inscripcion.area_nivel.asignacions', function ($q) use ($idPersona) {
                $q->where('id_persona', $idPersona);
            });
        }

        $query->where('id_fase', '=', $fase->fase->id);

        if ($estado_clasificado) {
            switch ($estado_clasificado) {
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

        if ($busqueda) {
            $query->whereHas('inscripcion.competidor', function ($q) use ($busqueda) {
                $q->where('nombres', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('apellidos', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('ci', 'ILIKE', "%{$busqueda}%");
            })->orWhereHas('inscripcion.area_nivel.area', function ($q) use ($busqueda) {
                $q->where('nombre_area', 'ILIKE', "%{$busqueda}%");
            });
        }
        switch ($ordenarPor) {
            case 'nombre':

                $direccion = strtolower($direccion);
                if (!in_array($direccion, ['asc', 'desc'])) {
                    $direccion = 'asc';
                }

                $query->orderBy(
                    Competidor::select('nombres')
                        ->join('inscripcion', 'inscripcion.id_competidor', '=', 'competidor.id')
                        ->whereColumn('inscripcion.id', 'evaluacion.id_inscripcion')
                        ->limit(1),
                    $direccion
                );

                break;

            default:
                $query->orderByRaw("$ordenarPor $direccion NULLS LAST");

                break;
        }
        // 1) Obtener todas las evaluaciones SIN paginar
        $evaluaciones = $query->get();

        // 2) Agrupar por equipo
        $agrupado = $evaluaciones
            ->groupBy(function ($eva) {
                return $eva->inscripcion->competidor->equipo->id ?? 0;
            })
            ->map(function ($items) {
                $primero = $items->first();
                $estado_clasificado = null;
                if ($primero->nota !== null) {
                    if ($primero->nota >= 51 && $primero->respeto && $primero->integridad && $primero->puntualidad) {
                        $estado_clasificado = "Clasificado";
                    } elseif ($primero->nota < 51 && $primero->respeto && $primero->integridad && $primero->puntualidad) {
                        $estado_clasificado = "No clasificado";
                    } elseif (!$primero->respeto || !$primero->integridad || !$primero->puntualidad) {
                        $estado_clasificado = "Descalificado";
                    }
                }
                return [
                    'equipo' => $primero->inscripcion->competidor->equipo->nombre_equipo ?? 'Sin equipo',
                    'id_equipo' => $primero->inscripcion->competidor->equipo->id ?? null,
                    'id_evaluacion' => $items->pluck('id')->toArray(),
                    'area' => $primero->inscripcion->area_nivel->area->nombre_area,
                    'nivel' => $primero->inscripcion->area_nivel->nivel->nombre_nivel,
                    'fase' => $primero->fase->nombre ?? 'Sin fase',
                    'nota' => $primero->nota,
                    'conducta' => [
                        'respeto' => $primero->respeto,
                        'integridad' => $primero->integridad,
                        'puntualidad' => $primero->puntualidad,
                    ],
                    'descripcion' => $primero->descripcion,
                    'estado_clasificado' => $estado_clasificado ?? null,
                    'estado' => $primero->estado,
                    'estado_confirmado' => $primero->estado_confirmacion,
                    'observacion' => $primero->observacion
                ];
            })
            ->values(); // reset keys

        // 3) Paginar manualmente la colección de equipos
        $total = $agrupado->count();
        $items = $agrupado->slice(($page - 1) * $perPage, $perPage)->values();

        // 4) Crear la paginación con LengthAwarePaginator
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query()
            ]
        );

        return $paginated;

    }
    public function obtenerEvaluacionesPorEvaluador($idPersona, $idAreaNivelFase, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion)
    {
        $persona = Persona::with('rols')->find($idPersona);
        $esEvaluadoor = $persona->rols->contains('nombre', 'evaluador');
        $fase = AreaNivelFase::with('fase')->find($idAreaNivelFase);
        Log::debug('fase', ['fase' => $fase, 'persoan' => $persona]);
        $query = Evaluacion::with([
            'fase',
            'inscripcion.competidor',
            'inscripcion.area_nivel.area',
            'inscripcion.area_nivel.nivel',
        ])
            ->whereHas('inscripcion.area_nivel.area_nivel_fase', function ($q) use ($idAreaNivelFase) {
                $q->where('id', $idAreaNivelFase);
            });
        if ($esEvaluadoor) {
            $query->whereHas('asignacion', function ($q) use ($idPersona) {
                $q->where('id_persona', $idPersona);
            });
            Log::debug('esta entrando individual');
        } else {
            $query->whereHas('inscripcion.area_nivel.asignacions', function ($q) use ($idPersona) {
                $q->where('id_persona', $idPersona);
            });
        }

        $query->where('id_fase', '=', $fase->fase->id);

        if ($estado_clasificado) {
            switch ($estado_clasificado) {
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

        if ($busqueda) {
            $query->whereHas('inscripcion.competidor', function ($q) use ($busqueda) {
                $q->where('nombres', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('apellidos', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('ci', 'ILIKE', "%{$busqueda}%");
            })->orWhereHas('inscripcion.area_nivel.area', function ($q) use ($busqueda) {
                $q->where('nombre_area', 'ILIKE', "%{$busqueda}%");
            });
        }
        switch ($ordenarPor) {
            case 'nombre':

                $direccion = strtolower($direccion);
                if (!in_array($direccion, ['asc', 'desc'])) {
                    $direccion = 'asc';
                }

                $query->orderBy(
                    Competidor::select('nombres')
                        ->join('inscripcion', 'inscripcion.id_competidor', '=', 'competidor.id')
                        ->whereColumn('inscripcion.id', 'evaluacion.id_inscripcion')
                        ->limit(1),
                    $direccion
                );

                break;

            default:
                $query->orderByRaw("$ordenarPor $direccion NULLS LAST");

                break;
        }

        $evaluaciones = $query->paginate($perPage, ['*'], 'page', $page);


        $evaluaciones->getCollection()->transform(function ($eva) {
            $estado_clasificado = null;
            if ($eva->nota !== null) {
                if ($eva->nota >= 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                    $estado_clasificado = "Clasificado";
                } elseif ($eva->nota < 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                    $estado_clasificado = "No clasificado";
                } elseif (!$eva->respeto || !$eva->integridad || !$eva->puntualidad) {
                    $estado_clasificado = "Descalificado";
                }
            }



            return [
                'id_competidor' => $eva->inscripcion->competidor->id,
                'id_evaluacion' => $eva->id,
                'ci' => $eva->inscripcion->competidor->ci,
                'nombre' => $eva->inscripcion->competidor->nombres,
                'area' => $eva->inscripcion->area_nivel->area->nombre_area,
                'nivel' => $eva->inscripcion->area_nivel->nivel->nombre_nivel,
                'fase' => $eva->fase->nombre ?? 'Sin fase',
                'nota' => $eva->nota,
                'conducta' => [
                    'respeto' => $eva->respeto,
                    'integridad' => $eva->integridad,
                    'puntualidad' => $eva->puntualidad,
                ],
                'descripcion' => $eva->descripcion,
                'estado_clasificado' => $estado_clasificado ?? null,
                'estado' => $eva->estado,
                'estado_confirmado' => $eva->estado_confirmacion,
                'observacion' => $eva->observacion
            ];
        });


        return $evaluaciones;
    }
    /*     public function filtrarEvaluaciones(
            $nivelNombre = null,
            $idFase = null,
            $idArea = null,
            $idNivel = null,
            $busqueda = null,
            $perPage = 10,
            $page = 1,
            $estado_clasificado = null,
            $ordenarPor = 'nombre',
            $direccion = 'asc'
        ) {

            try {
                $query = Evaluacion::with([
                    'fase',
                    'inscripcion.competidor',
                    'inscripcion.competidor.grado',
                    'inscripcion.area_nivel.area',
                    'inscripcion.area_nivel.nivel',
                ])
                    ->where('estado_confirmacion', 'aprobado');
                if ($idFase) {
                    $query->where('id_fase', '=', $idFase);
                }
                if ($idArea) {
                    $query->whereHas('inscripcion.area_nivel.area', function ($q) use ($idArea) {
                        $q->where('id_area', '=', $idArea);
                    });
                }
                if ($idNivel) {
                    $query->whereHas('inscripcion.area_nivel.nivel', function ($q) use ($idNivel) {
                        $q->where('id_nivel', '=', $idNivel);
                    });
                }
                if ($nivelNombre) {
                    $query->whereHas('inscripcion.competidor.grado', function ($q) use ($nivelNombre) {
                        $q->where('nombre_grado', 'ILIKE', "%{$nivelNombre}%");
                    });
                }

                if ($estado_clasificado) {
                    switch ($estado_clasificado) {
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

                if ($busqueda) {
                    $query->whereHas('inscripcion.competidor', function ($q) use ($busqueda) {
                        $q->where('nombres', 'ILIKE', "%{$busqueda}%")
                            ->orWhere('apellidos', 'ILIKE', "%{$busqueda}%")
                            ->orWhere('ci', 'ILIKE', "%{$busqueda}%");
                    })->orWhereHas('inscripcion.area_nivel.area', function ($q) use ($busqueda) {
                        $q->where('nombre_area', 'ILIKE', "%{$busqueda}%");
                    });
                }
                switch ($ordenarPor) {
                    case 'nombre':

                        $direccion = strtolower($direccion);
                        if (!in_array($direccion, ['asc', 'desc'])) {
                            $direccion = 'asc';
                        }

                        $query->orderBy(
                            Competidor::select('nombres')
                                ->join('inscripcion', 'inscripcion.id_competidor', '=', 'competidor.id')
                                ->whereColumn('inscripcion.id', 'evaluacion.id_inscripcion')
                                ->limit(1),
                            $direccion
                        );

                        break;

                    default:
                        $query->orderByRaw("$ordenarPor $direccion NULLS LAST");

                        break;
                }

                $evaluaciones = $query->paginate($perPage, ['*'], 'page', $page);


                $evaluaciones->getCollection()->transform(function ($eva) {
                    $estado_clasificado = null;
                    if ($eva->nota !== null) {
                        if ($eva->nota >= 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                            $estado_clasificado = "Clasificado";
                        } elseif ($eva->nota < 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                            $estado_clasificado = "No clasificado";
                        } elseif (!$eva->respeto || !$eva->integridad || !$eva->puntualidad) {
                            $estado_clasificado = "Descalificado";
                        }
                    }



                    return [
                        'id_competidor' => $eva->inscripcion->competidor->id,
                        'id_evaluacion' => $eva->id,
                        'ci' => $eva->inscripcion->competidor->ci,
                        'nombre' => $eva->inscripcion->competidor->nombres,
                        'grado' => $eva->inscripcion->competidor->grado->nombre_grado,
                        'area' => $eva->inscripcion->area_nivel->area->nombre_area,
                        'nivel' => $eva->inscripcion->area_nivel->nivel->nombre_nivel,
                        'fase' => $eva->fase->nombre ?? 'Sin fase',
                        'nota' => $eva->nota,
                        'conducta' => [
                            'respeto' => $eva->respeto,
                            'integridad' => $eva->integridad,
                            'puntualidad' => $eva->puntualidad,
                        ],
                        'descripcion' => $eva->descripcion,
                        'estado_clasificado' => $estado_clasificado ?? null,
                        'estado' => $eva->estado,
                        'estado_confirmado' => $eva->estado_confirmacion,
                        'observacion' => $eva->observacion
                    ];
                });


                $data = $evaluaciones->items();
                $meta = [
                    'current_page' => $evaluaciones->currentPage(),
                    'per_page' => $evaluaciones->perPage(),
                    'total' => $evaluaciones->total(),
                    'last_page' => $evaluaciones->lastPage(),
                    'next_page_url' => $evaluaciones->nextPageUrl(),
                    'prev_page_url' => $evaluaciones->previousPageUrl(),
                    'links' => $evaluaciones->linkCollection(),
                ];
                return [
                    'status_code' => 200,
                    'content' => [
                        'message' => 'Evaluaciones obtenidas correctamente.',
                        'data' => $data,
                        'meta' => $meta
                    ]

                ];

            } catch (\Exception $e) {

                $code = str_contains($e->getMessage(), 'No se encontraron') ? 404 : 500;
                return [
                    'status_code' => $code,
                    'content' => [
                        'status' => 'error',
                        'message' => 'Error al obtener las evaluaciones : ' . $e->getMessage(),
                    ],
                ];
            }
        }
     */
    public function filtrarEvaluaciones($publicado, $estado, $nivelNombre, $id_fase, $id_area, $id_nivel, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion)
    {
        if ($estado === "certificados") {
            return $this->filtrarEvaluacionesCertificados(
                $nivelNombre,
                $id_fase,
                $id_area,
                $id_nivel,
                $busqueda,
                $perPage,
                $page,
                $publicado,

            );
        } else if ($estado === "ceremonia") {
            return $this->filtrarEvaluacionesCeremonia(
                $nivelNombre,
                $id_fase,
                $id_area,
                $id_nivel,
                $busqueda,
                $perPage,
                $page,
                $publicado

            );
        } else {
            $res = $this->filtrarEvaluacionesRanking($nivelNombre, $id_fase, $id_area, $id_nivel, $busqueda, $perPage, $page, $estado_clasificado, $ordenarPor, $direccion, $publicado, );
        }
        return $res;
    }
    public function filtrarEvaluacionesCeremonia(
        $nivelNombre,
        $idFase,
        $idArea,
        $idNivel,
        $busqueda,
        $perPage,
        $page,
        $publicado
    ) {
        try {
            $query = $this->baseQueryEvaluaciones(
                $nivelNombre,
                $idFase,
                $idArea,
                $idNivel,
                $busqueda,
                $publicado
            )->orderBy('puesto_oficial', 'asc');
            ;
            $allData = $query->get()->map(function ($eva) {
                $areaNivelId = $eva->inscripcion->area_nivel->id;

                $config = \App\Models\ConfigMedallero::where('id_area_nivel', $areaNivelId)->first();

                $premio = null;

                if ($config && $eva->puesto_oficial) {

                    $puesto = $eva->puesto_oficial;

                    // Rangos:
                    $hastaOros = $config->oros;
                    $hastaPlatas = $config->oros + $config->platas;
                    $hastaBronces = $config->oros + $config->platas + $config->bronces;
                    $hastaMenciones = $hastaBronces + $config->menciones_honorificas;

                    if ($puesto >= 1 && $puesto <= $hastaOros) {
                        $premio = "Oro";
                    } elseif ($puesto <= $hastaPlatas) {
                        $premio = "Plata";
                    } elseif ($puesto <= $hastaBronces) {
                        $premio = "Bronce";
                    } elseif ($puesto <= $hastaMenciones) {
                        $premio = "Mención Honorífica";
                    }
                }
                if (!$premio)
                    return;
                return [
                    'id_ranking' => $eva->id,
                    'nombre_completo' => $eva->inscripcion->competidor->nombres . ' ' . $eva->inscripcion->competidor->apellidos,
                    'unidad_educativa' => $eva->inscripcion->competidor->institucion->nombre_institucion,
                    'area' => $eva->inscripcion->area_nivel->area->nombre_area,
                    'nivel' => $eva->inscripcion->area_nivel->nivel->nombre_nivel,
                    'puesto' => $eva->puesto_oficial,
                    'premio' => $premio
                ];


            })->filter()->values();

            $page = (int) $page;
            $total = $allData->count();
            $items = $allData->slice(($page - 1) * $perPage, $perPage)->values();
            $lastPage = ceil($total / $perPage);
            return [
                'status_code' => 200,
                'content' => [
                    'message' => 'Datos para certificados obtenidos correctamente.',
                    'data' => $items,
                    'meta' => [
                        'total' => $total,
                        'current_page' => $page,
                        'last_page' => $lastPage,
                    ]
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }
    public function filtrarEvaluacionesCertificados(
        $nivelNombre,
        $idFase,
        $idArea,
        $idNivel,
        $busqueda,
        $perPage,
        $page,

        $publicado,

    ) {
        try {
            $query = $this->baseQueryEvaluaciones(
                $nivelNombre,
                $idFase,
                $idArea,
                $idNivel,
                $busqueda,
                $publicado
            )->orderBy('puesto_oficial', 'asc');
            ;




            $allData = $query->get()->map(function ($eva) {


                $areaNivelId = $eva->inscripcion->area_nivel->id;

                $evaCompetidor = Evaluacion::where('id_inscripcion', $eva->id_inscripcion)
                    ->where('id_fase', $eva->id_fase)->first();
                $config = \App\Models\ConfigMedallero::where('id_area_nivel', $areaNivelId)->first();

                $premio = null;

                if ($config && $eva->puesto_oficial) {

                    $puesto = $eva->puesto_oficial;

                    // Rangos:
                    $hastaOros = $config->oros;
                    $hastaPlatas = $config->oros + $config->platas;
                    $hastaBronces = $config->oros + $config->platas + $config->bronces;
                    $hastaMenciones = $hastaBronces + $config->menciones_honorificas;

                    if ($puesto >= 1 && $puesto <= $hastaOros) {
                        $premio = "Oro";
                    } elseif ($puesto <= $hastaPlatas) {
                        $premio = "Plata";
                    } elseif ($puesto <= $hastaBronces) {
                        $premio = "Bronce";
                    } elseif ($puesto <= $hastaMenciones) {
                        $premio = "Mención Honorífica";
                    }
                }
                if (!$premio)
                    return null;
                return [
                    'id_ranking' => $eva->id,
                    'nombre_completo' => $eva->inscripcion->competidor->nombres . ' ' . $eva->inscripcion->competidor->apellidos,
                    'unidad_educativa' => $eva->inscripcion->competidor->institucion->nombre_institucion ?? 'Sin registro',
                    'departamento' => $eva->inscripcion->competidor->institucion->departamento_institucion ?? 'Sin registro',
                    'area' => $eva->inscripcion->area_nivel->area->nombre_area,
                    'nivel' => $eva->inscripcion->area_nivel->nivel->nombre_nivel,
                    'nota' => $eva->puntaje_total,
                    'puesto' => $eva->puesto_oficial,
                    'profesor' => $evaCompetidor->evaluador->nombres . ' ' . $evaCompetidor->evaluador->apellidos ?? 'No definido',
                    'responsable_area' => $eva->inscripcion->area_nivel->area->responsable->nombres . ' ' . $eva->inscripcion->area_nivel->area->responsable->apellidos ?? 'No definido',
                    'premio' => $premio
                ];


            })->filter()->values();
            $page = (int) $page;
            $total = $allData->count();
            $items = $allData->slice(($page - 1) * $perPage, $perPage)->values();
            $lastPage = ceil($total / $perPage);
            return [
                'status_code' => 200,
                'content' => [
                    'message' => 'Datos para certificados obtenidos correctamente.',
                    'data' => $items,
                    'meta' => [
                        'total' => $total,
                        'current_page' => $page,
                        'last_page' => $lastPage,
                    ]
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /*   private function getResponsbleEvaludor($idInscripcion, $idFase){
           $evaluacion = Evaluacion::where('id_inscripcion', $idInscripcion)
           ->where('id_fase',$idFase)->get();

      } */
    private function baseQueryEvaluaciones(
        $nivelNombre,
        $idFase,
        $idArea,
        $idNivel,
        $busqueda,
        $publicado
    ) {
        $query = Ranking::with([
            'fase',
            'inscripcion.competidor.grado',
            'inscripcion.competidor',
            'inscripcion.competidor.institucion',
            'inscripcion.area_nivel.area',
            'inscripcion.area_nivel.nivel',
            'inscripcion.evaluacions.asignacion.persona',
        ]);

        if ($idFase) {
            $query->where('id_fase', '=', $idFase);
        }
        if ($idArea) {
            $query->whereHas('inscripcion.area_nivel.area', function ($q) use ($idArea) {
                $q->where('id_area', '=', $idArea);
            });
        }
        if ($publicado) {
            $query->whereHas('inscripcion.evaluacions', function ($q) {
                $q->where('estado_confirmacion', '=', 'publicado');
            });
        }
        if ($idNivel) {
            $query->whereHas('inscripcion.area_nivel.nivel', function ($q) use ($idNivel) {
                $q->where('id_nivel', '=', $idNivel);
            });
        }
        if ($nivelNombre) {
            $query->whereHas('inscripcion.competidor.grado', function ($q) use ($nivelNombre) {
                $q->where('nombre_grado', 'ILIKE', "%{$nivelNombre}%");
            });
        }
        $query->where('estado_final', '=', "Clasificado");
        if ($busqueda) {
            $query->whereHas('inscripcion.competidor', function ($q) use ($busqueda) {
                $q->where('nombres', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('apellidos', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('ci', 'ILIKE', "%{$busqueda}%");
            });
        }
        return $query;
    }

    /**
     * Summary of filtrarEvaluacionesRanking
     * @param mixed $nivelNombre
     * @param mixed $idFase
     * @param mixed $idArea
     * @param mixed $idNivel
     * @param mixed $busqueda
     * @param mixed $perPage
     * @param mixed $page
     * @param mixed $estado_clasificado
     * @param mixed $ordenarPor
     * @param mixed $direccion
     * @return array{content: array, status_code: int|array{content: array{message: string, status: string}, status_code: int}}
     */
    public function filtrarEvaluacionesRanking(
        $nivelNombre = null,
        $idFase = null,
        $idArea = null,
        $idNivel = null,
        $busqueda = null,
        $perPage = 10,
        $page = 1,
        $estado_clasificado = null,
        $ordenarPor = 'nombre',
        $direccion = 'asc',
        $publicado = false
    ) {

        try {
            $query = Ranking::with([
                'fase',
                'inscripcion.competidor',
                'inscripcion.competidor.grado',
                'inscripcion.area_nivel.area',
                'inscripcion.area_nivel.nivel',
            ]);

            if ($publicado) {
                $query->whereHas('inscripcion.evaluacions', function ($q) {
                    $q->where('estado_confirmacion', '=', 'publicado');
                });
            }
            if ($idFase) {
                $query->where('id_fase', '=', $idFase);
            }
            if ($idArea) {
                $query->whereHas('inscripcion.area_nivel.area', function ($q) use ($idArea) {
                    $q->where('id_area', '=', $idArea);
                });
            }
            if ($idNivel) {
                $query->whereHas('inscripcion.area_nivel.nivel', function ($q) use ($idNivel) {
                    $q->where('id_nivel', '=', $idNivel);
                });
            }
            if ($nivelNombre) {
                $query->whereHas('inscripcion.competidor.grado', function ($q) use ($nivelNombre) {
                    $q->where('nombre_grado', 'ILIKE', "%{$nivelNombre}%");
                });
            }

            if ($estado_clasificado) {
                $query->where('estado_final', '=', $estado_clasificado);
            }

            if ($busqueda) {
                $query->whereHas('inscripcion.competidor', function ($q) use ($busqueda) {
                    $q->where('nombres', 'ILIKE', "%{$busqueda}%")
                        ->orWhere('apellidos', 'ILIKE', "%{$busqueda}%")
                        ->orWhere('ci', 'ILIKE', "%{$busqueda}%");
                })->orWhereHas('inscripcion.area_nivel.area', function ($q) use ($busqueda) {
                    $q->where('nombre_area', 'ILIKE', "%{$busqueda}%");
                });
            }
            switch ($ordenarPor) {
                case 'nombre':

                    $direccion = strtolower($direccion);
                    if (!in_array($direccion, ['asc', 'desc'])) {
                        $direccion = 'asc';
                    }

                    $query->orderBy(
                        Competidor::select('nombres')
                            ->join('inscripcion', 'inscripcion.id_competidor', '=', 'competidor.id')
                            ->whereColumn('inscripcion.id', 'ranking.id_inscripcion')
                            ->limit(1),
                        $direccion
                    );

                    break;

                default:
                    $query->orderByRaw("$ordenarPor $direccion NULLS LAST");

                    break;
            }

            $evaluaciones = $query->paginate($perPage, ['*'], 'page', $page);


            $evaluaciones->getCollection()->transform(function ($eva) {

                // 🔹 Obtener el área_nivel de la inscripción
                $areaNivelId = $eva->inscripcion->area_nivel->id;

                // 🔹 Obtener configuración del medallero de ese área/nivel
                $config = \App\Models\ConfigMedallero::where('id_area_nivel', $areaNivelId)->first();

                $premio = "Sin medalla";

                if ($config && $eva->puesto_oficial) {

                    $puesto = $eva->puesto_oficial;

                    // Rangos:
                    $hastaOros = $config->oros;
                    $hastaPlatas = $config->oros + $config->platas;
                    $hastaBronces = $config->oros + $config->platas + $config->bronces;
                    $hastaMenciones = $hastaBronces + $config->menciones_honorificas;

                    if ($puesto >= 1 && $puesto <= $hastaOros) {
                        $premio = "Oro";
                    } elseif ($puesto <= $hastaPlatas) {
                        $premio = "Plata";
                    } elseif ($puesto <= $hastaBronces) {
                        $premio = "Bronce";
                    } elseif ($puesto <= $hastaMenciones) {
                        $premio = "Mención Honorífica";
                    }
                }

                return [
                    'id_competidor' => $eva->inscripcion->competidor->id,
                    'ci' => $eva->inscripcion->competidor->ci,
                    'nombre' => $eva->inscripcion->competidor->nombres,
                    'grado' => $eva->inscripcion->competidor->grado->nombre_grado,
                    'area' => $eva->inscripcion->area_nivel->area->nombre_area,
                    'nivel' => $eva->inscripcion->area_nivel->nivel->nombre_nivel,
                    'fase' => $eva->fase->nombre ?? 'Sin fase',
                    'puntaje' => $eva->puntaje_total,
                    'descripcion' => $eva->observacion,
                    'estado_final' => $eva->estado_final,
                    'puesto' => $eva->puesto_oficial,
                    'premio' => $premio,
                ];
            });
            $data = $evaluaciones->items();
            $meta = [
                'current_page' => $evaluaciones->currentPage(),
                'per_page' => $evaluaciones->perPage(),
                'total' => $evaluaciones->total(),
                'last_page' => $evaluaciones->lastPage(),
                'next_page_url' => $evaluaciones->nextPageUrl(),
                'prev_page_url' => $evaluaciones->previousPageUrl(),
                'links' => $evaluaciones->linkCollection(),
            ];
            return [
                'status_code' => 200,
                'content' => [
                    'message' => 'Evaluaciones obtenidas correctamente.',
                    'data' => $data,
                    'meta' => $meta
                ]

            ];

        } catch (\Exception $e) {

            $code = str_contains($e->getMessage(), 'No se encontraron') ? 404 : 500;
            return [
                'status_code' => $code,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al obtener las evaluaciones : ' . $e->getMessage(),
                ],
            ];
        }
    }
    public function actualizarEvaluacion($request, $id)
    {
        try {
            $request->validate([
                'nota' => 'nullable|numeric|min:0|max:100',
                'descripcion' => 'nullable|string|max:500',
                'conducta.respeto' => 'nullable|boolean',
                'conducta.integridad' => 'nullable|boolean',
                'conducta.puntualidad' => 'nullable|boolean',
                'estado_confirmacion' => 'nullable|string',
                'observacion' => 'nullable|string|max:500',
            ], [
                'nota.required' => 'La nota es obligatoria.',
                'nota.numeric' => 'La nota debe ser un número.',
                'nota.min' => 'La nota no puede ser menor a 0.',
                'nota.max' => 'La nota no puede ser mayor a 100.',
            ]);

            // Obtener id del evaluador desde auth
            $evaluadorId = auth()->guard('sanctum')->user()->id;
            $datos = $request->all();
            // Obtener IP del cliente
            $ip = $request->ip();
            $evaluacion = $this->evaluacionRepository->findById($id);


            $antes = $evaluacion->only(['nota', 'descripcion', 'observacion', 'estado_confirmacion', 'respeto', 'integridad', 'puntualidad']);

            if (isset($datos['conducta'])) {
                $evaluacion->respeto = $datos['conducta']['respeto'] ?? $evaluacion->respeto;
                $evaluacion->integridad = $datos['conducta']['integridad'] ?? $evaluacion->integridad;
                $evaluacion->puntualidad = $datos['conducta']['puntualidad'] ?? $evaluacion->puntualidad;
            }

            $evaluacion->nota = $datos['nota'] ?? $evaluacion->nota;
            $evaluacion->descripcion = $datos['descripcion'] ?? $evaluacion->descripcion;
            $evaluacion->observacion = $datos['observacion'] ?? $evaluacion->observacion;
            $evaluacion->estado_confirmacion = $datos['estado_confirmacion'] ?? $evaluacion->estado_confirmacion;

            $evaluacion->estado = 'evaluado';


            $this->evaluacionRepository->update($evaluacion, $evaluacion->toArray());


            DB::table('evaluacion_auditoria')->insert([
                'id_evaluacion' => $evaluacion->id,
                'evaluador_id' => $evaluadorId,
                'cambios' => json_encode([
                    'antes' => $antes,
                    'despues' => $evaluacion->only(['nota', 'descripcion', 'respeto', 'integridad', 'puntualidad'])
                ]),
                'ip' => $ip,
                'created_at' => now()
            ]);


            $estadoClasificado = null;
            if ($evaluacion->nota !== null) {
                if ($evaluacion->nota >= 51 && $evaluacion->respeto && $evaluacion->integridad && $evaluacion->puntualidad) {
                    $estadoClasificado = "Clasificado";
                } elseif ($evaluacion->nota < 51 && $evaluacion->respeto && $evaluacion->integridad && $evaluacion->puntualidad) {
                    $estadoClasificado = "No clasificado";
                } elseif (!$evaluacion->respeto || !$evaluacion->integridad || !$evaluacion->puntualidad) {
                    $estadoClasificado = "Descalificado";
                }
            }
            $data = [
                "id" => $evaluacion->id,
                "nota" => $evaluacion->nota,
                "descripcion" => $evaluacion->descripcion,
                "estado" => $evaluacion->estado,
                "observacion" => $evaluacion->observacion,
                "estado_confirmacion" => $evaluacion->estado_confirmacion,
                'conducta' => [
                    'respeto' => $evaluacion->respeto,
                    'integridad' => $evaluacion->integridad,
                    'puntualidad' => $evaluacion->puntualidad,
                ],
                "estado_clasificado" => $estadoClasificado,
                "id_inscripcion" => $evaluacion->id_inscripcion,
                "id_fase" => $evaluacion->id_fase,
                "created_at" => $evaluacion->created_at,
                "updated_at" => $evaluacion->updated_at
            ];
            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Evaluaciones obtenidas correctamente.',
                    'data' => $data
                ]

            ];
        } catch (\Exception $e) {

            $code = str_contains($e->getMessage(), 'No se encontró') ? 404 : 500;

            return [
                'status_code' => $code,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al obtener las actividades por fase: ' . $e->getMessage(),
                ],
            ];
        }
    }


    public function getEstadosByEvaluador(int $evaluadorId, $fase = null)
    {
        // Cargar todas las relaciones necesarias (evita N+1)
        $query = $this->areaNivelFaseRepo->getAllWithEvaluacionesFases();

        // Filtramos por fase si se especifica
        if ($fase !== null) {
            if (is_numeric($fase)) {
                // Filtro por ID de fase
                $query = $query->where('id_fase', $fase);
            } else {
                // Filtro por nombre de fase
                $query = $query->filter(function ($anf) use ($fase) {
                    return strtolower($anf->fase?->nombre) === strtolower($fase);
                });
            }
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
                'estado' => $anf->estado,
                'resumen_evaluaciones' => $resumen,
                'progreso_evaluacion' => $progreso,
            ];
        }

        return $this->successResponse('Estados obtenidos correctamente', $resultado);
    }

    public function aprobarClasificadosEvaluador($idAreaNivelFase)
    {
        try {
            // Obtener todas las evaluaciones "Clasificado" de esa fase
            $evaluaciones = Evaluacion::where('id_area_nivel_fase', $idAreaNivelFase)
                ->where('estado_confirmacion', 'pendiente')
                ->where('estado_clasificado', 'Clasificado')
                ->get();

            foreach ($evaluaciones as $evaluacion) {
                $evaluacion->estado_confirmacion = 'aprobado';
                $evaluacion->observacion = 'Ninguno';
                $evaluacion->save();
            }

            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Aval otorgado correctamente a los clasificados.',
                    'total_aprobados' => $evaluaciones->count(),
                ]
            ];

        } catch (\Exception $e) {

            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error en el servidor: ' . $e->getMessage(),
                ],
            ];
        }
    }

    public function otorgarAvalResponsable($idAreaNivelFase)
    {
        try {
            // Obtener las evaluaciones relacionadas al área_nivel_fase indicado
            $evaluaciones = Evaluacion::whereHas('inscripcion.area_nivel.area_nivel_fase', function ($query) use ($idAreaNivelFase) {
                $query->where('id', $idAreaNivelFase);
            })

                ->get();

            // Si no hay evaluaciones, devolver mensaje 404
            if ($evaluaciones->isEmpty()) {
                return [
                    'status_code' => 404,
                    'content' => [
                        'status' => 'error',
                        'message' => 'No se encontraron evaluaciones para este área-nivel-fase',
                    ],
                ];

            }

            // Actualizar cada evaluación
            foreach ($evaluaciones as $evaluacion) {
                $evaluacion->update([
                    'estado_confirmacion' => 'aprobado',
                    'observacion' => 'Aval otorgado automáticamente'
                ]);
            }

            // Cambiar el estado del área_nivel_fase a "confirmado"
            $areaNivelFase = AreaNivelFase::find($idAreaNivelFase);
            if ($areaNivelFase) {
                $areaNivelFase->update(['estado' => 'confirmado']);
                $this->estadoService->migrarEvaluacionesRanking($idAreaNivelFase);
            } else {
                return [
                    'status_code' => 404,
                    'content' => [
                        'status' => 'error',
                        'message' => 'AreaNivelFase ID  no encontrado al intentar confirmar',
                    ],
                ];
            }

            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Avales otorgados correctamente y área-nivel-fase confirmado',
                    'total_avales' => $evaluaciones->count()
                ],
            ];


        } catch (\Exception $e) {
            \Log::error("Error en otorgarAval: " . $e->getMessage());
            return [
                'status_code' => 500,
                'content' => [
                    'status' => 'error',
                    'message' => 'Error al otorgar aval: ' . $e->getMessage(),
                ],
            ];
        }
    }

    public function obtenerCompetidoresEquipo($id)
    {
        $equipo = Equipo::find($id);
        if (!$equipo) {
            return [
                'status_code' => 400,
                'content' => [
                    'status' => 'error',
                    'message' => 'El usuario no tiene persona asociada'
                ]
            ];
        }
        $competidores = $equipo->competidors->map(function ($c) {
            return [
                'id' => $c->id,
                'ci' => $c->ci,
                'nombres' => $c->nombres,
                'apellidos' => $c->apellidos
            ];
        });

        return [
            'status_code' => 400,
            'content' => [
                'status' => 'success',
                'message' => 'Equipo obtenido correctamente',
                'data' => [
                    'id_equipo' => $equipo->id,
                    'equipo' => $equipo->nombre_equipo,
                    'competidores' => $competidores
                ],

            ]
        ];
    }

}
