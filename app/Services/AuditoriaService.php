<?php

namespace App\Services;

use App\Models\CertificadoLog;
use App\Models\Fase;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;

class AuditoriaService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function obtenerAuditoria($params)
    {
        $rol = $params['rol'] ?? null;
        $area = $params['area'] ?? null;
        $nivel = $params['nivel'] ?? null;
        $fase = $params['fase'] ?? null;
        $fecha = $params['fecha'] ?? null;
        $accion = $params['accion'] ?? null;
        $perPage = $params['perPage'] ?? 10;

        // === BASE PARA ESTADÍSTICAS ===
        $queryBase = DB::table('evaluacion_auditoria AS ea');

        // === QUERY PRINCIPAL (CON JOINS) ===
        $query = DB::table('evaluacion_auditoria AS ea')
            ->join('evaluacion AS e', 'e.id', '=', 'ea.id_evaluacion')
            ->join('inscripcion AS i', 'i.id', '=', 'e.id_inscripcion')
            ->join('competidor AS comp', 'comp.id', '=', 'i.id_competidor')
            ->join('area_nivel AS an', 'an.id', '=', 'i.id_area_nivel')
            ->join('area AS a', 'a.id', '=', 'an.id_area')
            ->join('nivel AS n', 'n.id', '=', 'an.id_nivel')
            ->leftJoin('fase AS f', 'f.id', '=', 'e.id_fase')
            ->leftJoin('persona AS p', 'p.id', '=', 'ea.evaluador_id')
            ->leftJoin('persona_rol AS pr', 'pr.id_persona', '=', 'p.id')
            ->leftJoin('rol AS r', 'r.id', '=', 'pr.id_rol')
            ->select(
                'ea.id',
                'ea.accion AS accion',
                'ea.created_at AS fecha',
                DB::raw("CONCAT(comp.nombres, ' ', comp.apellidos) AS competidor"),
                'a.nombre_area AS area',
                'n.nombre_nivel AS nivel',
                'f.nombre AS fase',
                'ea.cambios',
                DB::raw("CONCAT(p.nombres, ' ', p.apellidos) AS usuario"),
                'r.nombre AS rol',
                'ea.accion AS tipo_cambio',
                'ea.motivo'
            )
            ->orderBy('ea.id', 'desc');

        // === FILTROS ===
        if ($accion) {
            $query->where('ea.accion', $accion);
            $queryBase->where('ea.accion', $accion);
        }
        if ($rol) {
            $query->where('r.id', $rol);
        }
        if ($area) {
            $query->where('a.id', $area);
        }
        if ($nivel) {
            $query->where('n.id', $nivel);
        }
        if ($fecha) {
            $query->whereDate('ea.created_at', $fecha);
        }
        if ($fase) {
            $query->where('f.id', $fase);
        }

        // === ESTADÍSTICAS ===
        $hoy = now()->toDateString();

        $meta = [
            'total_registros' => (clone $queryBase)->count(),
            'registros_hoy' => (clone $queryBase)->whereDate('ea.created_at', $hoy)->count(),
            'total_insert' => (clone $queryBase)->where('ea.accion', 'insert')->count(),
            'total_update' => (clone $queryBase)->where('ea.accion', 'update')->count(),
        ];

        // === PAGINACIÓN ===
        $logs = $query->paginate($perPage);

        // === TRANSFORMAR JSON ===
        $data = $logs->getCollection()->transform(function ($item) {
            $cambios = json_decode($item->cambios, true);

            return [
                'id' => $item->id,
                'fecha_hora' => $item->fecha,
                'competidor' => $item->competidor,
                'area' => $item->area,
                'nivel' => $item->nivel,
                'fase' => $item->fase,
                'accion' => $item->accion,
                'nota_antes' => $cambios['antes']['nota'] ?? null,
                'nota_despues' => $cambios['despues']['nota'] ?? null,
                'desc_antes' => $cambios['antes']['descripcion'] ?? null,
                'desc_despues' => $cambios['despues']['descripcion'] ?? null,
                'usuario' => $item->usuario,
                'rol' => $item->rol,
                'tipo_cambio' => $item->tipo_cambio,
                'motivo' => $item->motivo
            ];
        });

        // META FINAL PAGINACIÓN + ESTADÍSTICAS
        $meta = array_merge($meta, [
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
            'from' => $logs->firstItem(),
            'to' => $logs->lastItem(),
            'has_more_pages' => $logs->hasMorePages(),
        ]);


        return [
            'status_code' => 200,
            'content' => [
                'message' => 'Logs obtenidos correctamente.',
                'data' => $data,
                'meta' => $meta
            ]
        ];
    }
    public function getCertificadoslogs($params)
    {
        try {
            $area = $params['area'] ?? null;
            $nivel = $params['nivel'] ?? null;
            $query = DB::table('certificado_logs AS cl')
                ->join('inscripcion AS i', 'i.id', '=', 'cl.id_inscripcion')
                ->join('competidor AS comp', 'comp.id', '=', 'i.id_competidor')
                ->join('area_nivel AS an', 'an.id', '=', 'i.id_area_nivel')
                ->join('area AS a', 'a.id', '=', 'an.id_area')
                ->join('nivel AS n', 'n.id', '=', 'an.id_nivel')
                ->leftJoin('fase AS f', 'f.id', '=', 'cl.id_fase')
                ->leftJoin('persona AS p', 'p.id', '=', 'cl.realizado_por')
                ->select(
                    'cl.id',
                    'cl.accion AS accion',
                    'cl.created_at AS fecha',
                    DB::raw("CONCAT(comp.nombres, ' ', comp.apellidos) AS competidor"),
                    'a.nombre_area AS area',
                    'n.nombre_nivel AS nivel',
                    'f.nombre AS fase',
                    DB::raw("CONCAT(p.nombres, ' ', p.apellidos) AS usuario"),
                )
                ->orderBy('cl.id', 'desc');

            if ($area) {
                $query->where('a.id', $area);
            }
            if ($nivel) {
                $query->where('n.id', $nivel);
            }

            $logs = $query->paginate(10);
            $data = $logs->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'fecha_hora' => $item->fecha,
                    'competidor' => $item->competidor,
                    'area' => $item->area,
                    'nivel' => $item->nivel,
                    'fase' => $item->fase,
                    'accion' => $item->accion,
                    'usuario' => $item->usuario,
                ];
            });
            $meta = [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
                'has_more_pages' => $logs->hasMorePages(),
            ];
            return [
                'status_code' => 200,
                'content' => [
                    'message' => 'Logs obtenidos correctamente.',
                    'data' => $data,
                    'meta' => $meta
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
    public function generarCertificado($request)
    {
        try {
            $request->validate([
                'id_inscripcion' => 'required|integer',
            ]);

            $fase = Fase::where('nombre', 'final')->first();
            $idInscripcion = $request->id_inscripcion;
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $user = auth()->guard('sanctum')->user();
            $persona = Persona::where('id_usuario', $user->id)->first();

            CertificadoLog::create([
                'id_inscripcion' => $idInscripcion,
                'id_fase' => $fase->id,
                'accion' => 'Generado',
                'archivo' => null,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'realizado_por' => $persona->id,
            ]);

            return [
                'status_code' => 200,
                'content' => [
                    'status' => 'success',
                    'message' => 'Certificado generado correctamente.',
                ],
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
}
