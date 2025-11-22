<?php

namespace App\Repositories;

use App\Models\Actividad;
use Illuminate\Support\Facades\Log;

class ActividadRepository
{
    protected $model;
    public function __construct(Actividad $model)
    {
        $this->model = $model;
    }
    public function all()
    {
        return $this->model::all();
    }
    public function findById(int $id)
    {
        return $this->model::find($id);
    }
    public function getByFaseId($faseId)
    {
        return Actividad::where('id_fase', $faseId)
            ->where('estado_publicado', 'sin_fechas')
            ->select('id', 'nombre')
            ->get();
    }
    public function findNombre(string $nombre)
    {
        return $this->model::where('nombre', $nombre)->first();
    }
    public function findByNombreYFase(string $nombreFase, string $nombreActividad)
    {
        /*   Log::debug('data', [
              'nombreActividad' => $nombreActividad,
              'nombreFase' => $nombreFase
          ]); */

        return $this->model::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombreActividad)])
            ->whereHas('fase', function ($query) use ($nombreFase) {
                $query->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombreFase)]);
            })
            ->first();
    }
    public function create(array $data)
    {
        return $this->model::create($data);
    }
    public function update($id, array $data)
    {
        $actividad = $this->findById($id);
        $actividad->update($data);
        return $actividad;
    }
}
