<?php

namespace App\Exports;

use App\Repositories\EvaluacionRepository;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ListaResultadosExport implements FromCollection, WithHeadings
{
    protected $evaluaciones;

    public function __construct($evaluaciones)
    {
        $this->evaluaciones = $evaluaciones;
    }
    public function collection()
    {
        return collect($this->evaluaciones)->map(function ($eva) {
            return [
                $eva['nombre'],
                $eva['area'],
                $eva['nivel'],
                $eva['grado '],
                $eva['nota'],
                $eva['estado_clasificado'],
                $eva['descripcion'],
            ];
        });

    }
    public function headings(): array
    {
        return [
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
