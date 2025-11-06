<?php

namespace App\Http\Controllers;

use App\Services\EstadosServide;
use App\Services\CierreFaseService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrepararEntornoFinalController extends Controller
{
    use ApiResponseTrait;
    protected $cierreFaseService;

    public function __construct(CierreFaseService $cierreFaseService)
    {
        $this->cierreFaseService = $cierreFaseService;
    }
    public function index()
    {
        // Opcional: filtrar por olimpiada, fase, área, nivel


        // Llamamos al servicio que devuelve resumen y progreso
        $resultado = $this->cierreFaseService->getAllEstadoAreaNivelConfirmado();

        return response()->json(
            $resultado
        );
    }
    public function prepararEntornoFinalPorAreaNivelFase(int $idAreaNivelFase)
    {
        DB::beginTransaction();

        try {
            // 1️⃣ Obtener el área_nivel_fase (fase clasificatoria)
            $areaFase = DB::table('area_nivel_fase')->where('id', $idAreaNivelFase)->first();

            if (!$areaFase) {
                throw new \Exception("No existe el área_nivel_fase con ID $idAreaNivelFase.");
            }

            if ($areaFase->estado !== 'confirmado') {
                throw new \Exception("El área_nivel_fase no está en estado Confirmado.");
            }

            // 2️⃣ Obtener la fase final ("Final")
            $faseFinal = DB::table('fase')->where('nombre', 'Final')->first();
            if (!$faseFinal) {
                throw new \Exception("No existe la fase final 'Evaluación' en la tabla fase.");
            }

            // 3️⃣ Crear la nueva relación area_nivel_fase (para la fase final)
            $nuevoAreaNivelFaseId = DB::table('area_nivel_fase')->insertGetId([
                'estado' => 'En_evaluacion',
                'fecha_ini' => now(),
                'fecha_fin' => now()->addDays(15),
                'id_area_nivel' => $areaFase->id_area_nivel,
                'id_fase' => $faseFinal->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4️⃣ Migrar solo los CLASIFICADOS (nota ≥ 51 y conducta positiva),
            // pero SIN nota ni descripción
            $migrados = DB::insert("
            INSERT INTO evaluacion (nota, descripcion, estado, id_inscripcion, id_fase, created_at, updated_at)
            SELECT 
                NULL AS nota,
                NULL AS descripcion,
                'pendiente',
                e.id_inscripcion,
                :idFaseFinal,
                NOW(),
                NOW()
            FROM evaluacion e
            JOIN inscripcion i ON i.id = e.id_inscripcion
            WHERE i.id_area_nivel = :idAreaNivel
              AND e.id_fase = :idFaseClasificatoria
              AND e.nota >= 51
              AND e.respeto = TRUE
              AND e.integridad = TRUE
              AND e.puntualidad = TRUE
        ", [
                'idFaseFinal' => $faseFinal->id,
                'idAreaNivel' => $areaFase->id_area_nivel,
                'idFaseClasificatoria' => $areaFase->id_fase,
            ]);

            // 5️⃣ Actualizar estado del área_nivel_fase anterior
            DB::table('area_nivel_fase')
                ->where('id', $idAreaNivelFase)
                ->update([
                    'estado' => 'Concluido',
                    'updated_at' => now(),
                ]);

            DB::commit();

            return $this->successResponse("✅ Entorno final preparado correctamente.", [
                'id_area_nivel_fase_origen' => $idAreaNivelFase,
                'id_area_nivel_fase_final' => $nuevoAreaNivelFaseId,
                'total_migrados' => $migrados
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('❌ Error al preparar el entorno final.', $e->getMessage());
        }
    }


}
