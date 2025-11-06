<?php

namespace App\Exports;

use App\Models\Evaluacion;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EvaluacionesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $idEvaluador;
    protected $busqueda;
    protected $estado_clasificado;
    protected $idAreaNivelFase;
    public function __construct($idEvaluador, $busqueda = null, $estado_clasificado = null, $idAreaNivelFase = null)
    {
        $this->idEvaluador = $idEvaluador;
        $this->busqueda = $busqueda;
        $this->estado_clasificado = $estado_clasificado;
        $this->idAreaNivelFase = $idAreaNivelFase;
    }

    public function collection()
    {
        $fase = \App\Models\AreaNivelFase::with('fase')->find($this->idAreaNivelFase);
        $esFaseFinal = $fase && strtolower($fase->fase->nombre) === 'final';
        // Copia el query de tu repository (sin paginación)
        $query = Evaluacion::with([
            'inscripcion.competidor',
            'inscripcion.area_nivel.area',
            'inscripcion.area_nivel.nivel'
        ])
            ->whereHas('inscripcion.area_nivel.asignacions.persona.rols', function ($q) {
                $q->where('nombre', 'evaluador');
            })
            ->whereHas('inscripcion.area_nivel.asignacions', function ($q) {
                $q->where('id_persona', $this->idEvaluador);
            })
            ->whereHas('inscripcion.area_nivel.area_nivel_fase', function ($q) {
                $q->where('id', $this->idAreaNivelFase);
            });


        if ($esFaseFinal) {
            Log::debug('Fase final', [$esFaseFinal]);
            $query->where('estado_confirmacion', '!=', 'aprobado');
        } else {
            $query->where('estado_confirmacion', '!=', 'pendiente');

        }
        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->whereHas('inscripcion.competidor', function ($q2) {
                    $q2->where('nombres', 'ILIKE', "%{$this->busqueda}%")
                        ->orWhere('apellidos', 'ILIKE', "%{$this->busqueda}%")
                        ->orWhere('ci', 'ILIKE', "%{$this->busqueda}%");
                })
                    ->orWhereHas('inscripcion.area_nivel.area', function ($q2) {
                        $q2->where('nombre_area', 'ILIKE', "%{$this->busqueda}%");
                    });
            });
        }

        if ($this->estado_clasificado) {
            switch ($this->estado_clasificado) {
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

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'CI',
            'Nombre',
            'Área',
            'Nivel',
            'Nota',
            'Respeto',
            'Integridad',
            'Puntualidad',
            'Estado Clasificado',
        ];
    }

    public function map($eva): array
    {
        // Recalcula estado clasificado
        $estado = null;
        if ($eva->nota !== null) {
            if ($eva->nota >= 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                $estado = 'Clasificado';
            } elseif ($eva->nota < 51 && $eva->respeto && $eva->integridad && $eva->puntualidad) {
                $estado = 'No clasificado';
            } elseif (!$eva->respeto || !$eva->integridad || !$eva->puntualidad) {
                $estado = 'Descalificado';
            }
        }

        return [
            $eva->inscripcion->competidor->ci ?? '',
            $eva->inscripcion->competidor->nombres ?? '',
            $eva->inscripcion->area_nivel->area->nombre_area ?? '',
            $eva->inscripcion->area_nivel->nivel->nombre_nivel ?? '',
            $eva->nota ?? '',
            $eva->respeto ? 'Sí' : 'No',
            $eva->integridad ? 'Sí' : 'No',
            $eva->puntualidad ? 'Sí' : 'No',
            $estado,
        ];
    }
}
