<?php

namespace App\Exports;

use App\Repositories\EvaluacionRepository;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ListaResultadosExport implements FromCollection, WithHeadings
{
    protected $evaluaciones;
    protected $estado;

    public function __construct($evaluaciones, $estado)
    {
        $this->evaluaciones = $evaluaciones;
        $this->estado = $estado;
    }
    public function collection()
    {

        if ($this->estado === "ceremonia") {
            return collect($this->evaluaciones)->map(function ($eva) {
                return [
                    $eva['nombre_completo'],
                    $eva['unidad_educativa'],
                    $eva['area'],
                    $eva['nivel'],
                    $eva['puesto'],
                ];
            });
        } else if ($this->estado === "certificados") {
            return collect($this->evaluaciones)->map(function ($eva) {
                return [
                    $eva['nombre_completo'],
                    $eva['unidad_educativa'],
                    $eva['departamento'],
                    $eva['area'],
                    $eva['nivel'],
                    $eva['nota'],
                    $eva['puesto'],
                    $eva['responsable_area'],
                    $eva['profesor'],
                ];
            });
        } else {
            return collect($this->evaluaciones)->map(function ($eva) {
                return [
                    $eva['nombre'],
                    $eva['area'],
                    $eva['nivel'],
                    $eva['grado'],
                    $eva['puntaje'],
                    $eva['estado_final'],
                    $eva['descripcion']
                ];
            });
        }


    }
    public function headings(): array
    {

        if ($this->estado === "ceremonia") {
            return [
                'Nombre Completo',
                'Unidad Educativa',
                'Area',
                'Nivel',
                'Posición',
            ];
        } else if ($this->estado === "certificados") {
            return [
                'Nombre Completo',
                'Unidad Educativa',
                'Departamento',
                'Area',
                'Nivel',
                'Nota',
                'Posición',
                'Profesor',
                'Responsable de area'
            ];
        } else {
            return [
                'Puesto',
                'Nombre del Competidor',
                'Área de Participación',
                'Nivel',
                'Grado',
                'Nota Final',
                'Estado Clasificación',
                'Descripción / Observación',
            ];
        }
    }

}
