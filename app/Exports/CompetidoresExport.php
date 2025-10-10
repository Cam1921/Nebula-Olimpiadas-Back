<?php

namespace App\Exports;

use App\Models\Competidor;
use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompetidoresExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $areaId;
    protected $nivelId;
    protected $busqueda;

    public function __construct($areaId = null, $nivelId = null, $busqueda = null)
    {
        $this->areaId = $areaId;
        $this->nivelId = $nivelId;
        $this->busqueda = $busqueda;
    }

    public function collection()
    {
        $query = Competidor::query()
            ->select([
                'competidor.id',
                DB::raw("CONCAT(competidor.nombres, ' ', competidor.apellidos) as nombre"),
                'competidor.ci',
                'grado.nombre_grado as grado',
                'institucion.nombre_institucion as unidad_educativa',
                'institucion.departamento_institucion as departamento',
                'area.nombre_area as area',
                'nivel.nombre_nivel as nivel',
                'tutor_legal.telefono as contacto_tutor_legal',
                'tutor_academico.telefono as contacto_tutor_academico'
            ])
            ->join('inscripcion', 'inscripcion.id_competidor', '=', 'competidor.id')
            ->join('area_nivel', 'area_nivel.id', '=', 'inscripcion.id_area_nivel')
            ->join('area', 'area.id', '=', 'area_nivel.id_area')
            ->join('nivel', 'nivel.id', '=', 'area_nivel.id_nivel')
            ->leftJoin('institucion', 'institucion.id', '=', 'competidor.id_institucion')
            ->leftJoin('grado', 'grado.id', '=', 'competidor.id_grado')
            ->leftJoin('tutor as tutor_legal', 'tutor_legal.id', '=', 'competidor.id_tutor_legal')
            ->leftJoin('tutor as tutor_academico', 'tutor_academico.id', '=', 'inscripcion.id_tutor_academico');


        if ($this->areaId) {
            $query->where('area_nivel.id_area', $this->areaId);
        }

        if ($this->nivelId) {
            $query->where('area_nivel.id_nivel', $this->nivelId);
        }


        if ($this->busqueda) {
            $busqueda = $this->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where(DB::raw("CONCAT(competidor.nombres, ' ', competidor.apellidos)"), 'LIKE', "%{$busqueda}%")
                    ->orWhere('competidor.ci', 'LIKE', "%{$busqueda}%")
                    ->orWhere('institucion.nombre_institucion', 'LIKE', "%{$busqueda}%");
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'CI',
            'Grado',
            'Unidad Educativa',
            'Departamento',
            'Área',
            'Nivel',
            'Contacto Tutor Legal',
            'Contacto Tutor Académico',
        ];
    }
}
