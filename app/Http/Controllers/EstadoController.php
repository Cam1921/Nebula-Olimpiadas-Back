<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AreaNivelFase;
use App\Services\EstadosServide;
use Illuminate\Http\Request;

class EstadoController extends Controller
{
    protected $estadoService;

    public function __construct(EstadosServide $estadoServide)
    {
        $this->estadoService = $estadoServide;
    }
    public function index()
    {
        // Opcional: filtrar por olimpiada, fase, área, nivel


        // Llamamos al servicio que devuelve resumen y progreso
        $resultado = $this->estadoService->getAllEstadoAreaNivel();

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }
    public function actualizarEstado(Request $request, $id)
    {
        try {
            $area = AreaNivelFase::findOrFail($id);

            $nuevoEstado = $request->input('estado');
            if (!$nuevoEstado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar un estado válido.',
                ], 400);
            }

            $area->estado = $nuevoEstado;
            $area->save();

            return response()->json([
                'success' => true,
                'message' => "Estado actualizado correctamente a '{$nuevoEstado}'.",
                'data' => $area,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function getEstadosAreas()
    {
        $areas = Area::select('id', 'nombre_area')->get();

        $data = $areas->map(function ($area) {
            // Obtenemos todos los estados asociados al área
            $estados = AreaNivelFase::whereHas('area_nivel', function ($query) use ($area) {
                $query->where('id_area', $area->id);
            })->pluck('estado');

            if ($estados->isEmpty()) {
                $estadoGeneral = 'Sin estado';
            }
            // 🔹 Si alguna fase sigue "En_evaluacion", el área general está "En evaluación"
            elseif ($estados->contains(fn($e) => $e === 'En_evaluacion')) {
                $estadoGeneral = 'En evaluación';
            }
            // 🔹 Si todas están "Concluido"
            elseif ($estados->every(fn($e) => $e === 'Concluido')) {
                $estadoGeneral = 'Concluido';
            }
            // 🔹 Si todas están "Confirmado"
            elseif ($estados->every(fn($e) => $e === 'Confirmado')) {
                $estadoGeneral = 'Confirmado';
            }
            // 🔹 Si todas están "Publicado"
            elseif ($estados->every(fn($e) => $e === 'Publicado')) {
                $estadoGeneral = 'Publicado';
            }
            // 🔹 Si todas están "Cerrado"
            elseif ($estados->every(fn($e) => $e === 'Cerrado')) {
                $estadoGeneral = 'Cerrado';
            } else {
                $estadoGeneral = 'Mixto'; // Cuando hay una mezcla de estados
            }

            return [
                'id' => $area->id,
                'nombre' => $area->nombre_area,
                'estado' => $estadoGeneral,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
