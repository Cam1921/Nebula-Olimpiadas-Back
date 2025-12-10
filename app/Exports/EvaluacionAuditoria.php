<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EvaluacionAuditoria implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $logs;
    public function __construct($logs)
    {
        $this->logs = $logs;
    }
    public function collection()
    {
        return collect($this->logs)->map(function ($log) {
            return [
                $log['fecha_hora'],
                $log['competidor'],
                $log['area'],
                $log['nivel'],
                $log['fase'],
                $log['nota_antes'],
                $log['nota_despues'],
                $log['usuario'],
                $log['rol'],
                $log['tipo_cambio'],
                $log['motivo'],
            ];
        });
    }
    public function headings(): array
    {

        return [
            'Fecha/Hora',
            'Competidor',
            'Area',
            'Nivel',
            'Fase',
            'Nota Antes',
            'Nota Despues',
            'Usuario',
            'Rol',
            'Tipo de Acción',
            'Motivo',
        ];
    }
}
