<?php

namespace App\Services;

use App\Repositories\TutorRepository;
use App\Traits\NormalizeStringTrait;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Repositories\PersonaRepository;
use App\Repositories\InstitucionRepository;
use App\Repositories\CompetidorRepository;
use App\Repositories\AreaRepository;
use App\Repositories\NivelRepository;
use App\Repositories\GradoRepository;
use App\Repositories\AreaNivelRepository;
use App\Repositories\InscripcionRepository;
use App\Repositories\ListaInscripcionRepository;
use App\Repositories\OlimpiadaRepository;
use Str;

class ListaCompetidoresService
{
    use NormalizeStringTrait, ApiResponseTrait;

    protected $tutorRepo;
    protected $institucionRepo;
    protected $competidorRepo;
    protected $areaRepo;
    protected $nivelRepo;
    protected $gradoRepo;
    protected $areaNivelRepo;
    protected $inscripcionRepo;
    protected $listaInscripcionRepo;
    protected $olimpiadaRepo;

    public function __construct(
        TutorRepository $tutorRepo,
        InstitucionRepository $institucionRepo,
        CompetidorRepository $competidorRepo,
        AreaRepository $areaRepo,
        NivelRepository $nivelRepo,
        GradoRepository $gradoRepo,
        AreaNivelRepository $areaNivelRepo,
        InscripcionRepository $inscripcionRepo,
        ListaInscripcionRepository $listaInscripcionRepo,
        OlimpiadaRepository $olimpiadaRepo
    ) {
        $this->tutorRepo = $tutorRepo;
        $this->institucionRepo = $institucionRepo;
        $this->competidorRepo = $competidorRepo;
        $this->areaRepo = $areaRepo;
        $this->nivelRepo = $nivelRepo;
        $this->gradoRepo = $gradoRepo;
        $this->areaNivelRepo = $areaNivelRepo;
        $this->inscripcionRepo = $inscripcionRepo;
        $this->listaInscripcionRepo = $listaInscripcionRepo;
        $this->olimpiadaRepo = $olimpiadaRepo;
    }

    /**
     * PREVIEW: Validar CSV
     */
    public function previewCsv($file)
    {
        if (!$file) {
            return $this->errorResponse(
                'Archivo no encontrado o no es un CSV válido',
                [['field' => 'archivo', 'error' => 'Archivo inválido']],
                400
            );
        }

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        $headers = array_map(fn($h) => $this->normalizeString($h), $headers);

        $required = ['nombre completo', 'ci', 'contacto tutor legal', 'unidad educativa', 'departamento', 'grado', 'area', 'nivel'];
        $required_normalized = array_map(fn($h) => $this->normalizeString($h), $required);

        $missing_required = array_diff($required_normalized, $headers);
        if (!empty($missing_required)) {
            return $this->errorResponse(
                'Encabezados requeridos faltantes',
                [['field' => 'headers', 'error' => implode(', ', $missing_required)]],
                400,
                ['found_headers' => $headers]
            );
        }

        $optional = ['contacto tutor academico'];
        $optional_normalized = array_map(fn($h) => $this->normalizeString($h), $optional);
        $missing_optional = array_diff($optional_normalized, $headers);
        $warnings = [];
        if (!empty($missing_optional)) {
            $warnings[] = ['field' => 'headers', 'warning' => 'Opcionales faltantes: ' . implode(', ', $missing_optional)];
        }

        $validos = [];
        $errores = [];
        $filaIndex = 1;
        $olimpiada = $this->olimpiadaRepo->getOlimpiadaActiva();

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $filaIndex++;
            $fila = [];
            foreach ($headers as $i => $colName) {
                $fila[$colName] = isset($data[$i]) ? trim($data[$i]) : null;
            }

            $filaErrores = [];

            foreach ($required_normalized as $campo) {
                if (empty($fila[$campo])) {
                    $filaErrores[] = [
                        'row' => $filaIndex,
                        'field' => $campo,
                        'error' => "El campo '$campo' no puede estar vacío"
                    ];
                }
            }

            $gradoObj = $this->gradoRepo->findByNombre($fila['grado']);
            $areaObj = $this->areaRepo->findByNombre($fila['area']);
            $nivelObj = $this->nivelRepo->findByNombre($fila['nivel']);

            if (!$gradoObj) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'grado', 'error' => "El grado '{$fila['grado']}' no existe"];
            }
            if (!$areaObj) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'area', 'error' => "El área '{$fila['area']}' no existe"];
            }
            if (!$nivelObj) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'nivel', 'error' => "El nivel '{$fila['nivel']}' no existe"];
            }

            if ($gradoObj && $nivelObj && !$this->gradoRepo->isGradoEnNivel($gradoObj->id, $nivelObj->id)) {
                $filaErrores[] = [
                    'row' => $filaIndex,
                    'field' => 'grado/nivel',
                    'error' => "El grado '{$fila['grado']}' no corresponde al nivel '{$fila['nivel']}'"
                ];
            }

            if ($areaObj && $nivelObj) {
                $areaNivel = $this->areaNivelRepo->findAreaNivel($areaObj->id, $nivelObj->id, $olimpiada->id);
                if (!$areaNivel) {
                    $filaErrores[] = [
                        'row' => $filaIndex,
                        'field' => 'area/nivel',
                        'error' => "No existe relación área-nivel configurada para la olimpiada"
                    ];
                }
            }

            if (empty($filaErrores)) {
                $validos[] = ['row' => $filaIndex, 'attributes' => $fila];
            } else {
                $errores = array_merge($errores, $filaErrores);
            }
        }
        fclose($handle);

        $meta = [
            'import_id' => (string) Str::uuid(),
            'total_rows' => count($validos) + count($errores),
            'valid_rows' => count($validos),
            'invalid_rows' => count($errores)
        ];

        if (!empty($errores)) {
            return $this->errorResponse(
                'Se encontraron errores en la validación del CSV',
                $errores,
                422,
                $meta
            );
        }

        return $this->successResponse(
            'Validación de CSV completada',
            $validos,
            $meta,
            200,
            $warnings
        );
    }

    /**
     * CONFIRMAR: Guardar CSV
     */
    public function confirmarCsv(array $filasValidas)
    {
        if (empty($filasValidas)) {
            return $this->errorResponse(
                'No se enviaron filas válidas para importar',
                [],
                422
            );
        }

        DB::beginTransaction();
        try {
            $olimpiada = $this->olimpiadaRepo->getOlimpiadaActiva();
            $listaInscripcion = $this->listaInscripcionRepo->firstOrCreateLista($olimpiada->id);

            $importados = [];
            foreach ($filasValidas as $fila) {
                $gradoObj = $this->gradoRepo->findByNombre($fila['grado']);
                $areaObj = $this->areaRepo->findByNombre($fila['area']);
                $nivelObj = $this->nivelRepo->findByNombre($fila['nivel']);
                $areaNivel = $this->areaNivelRepo->findAreaNivel($areaObj->id, $nivelObj->id, $olimpiada->id);

                $tutorLegal = $this->tutorRepo->firstOrCreatePersona(['telefono' => $fila['contacto tutor legal']]);
                $institucion = $this->institucionRepo->firstOrCreateInstitucion([
                    'nombre_institucion' => $fila['unidad educativa'],
                    'departamento_institucion' => $fila['departamento'],
                ]);

                $nombreSeparado = separarNombreCompleto($fila['nombre completo']);

                $competidor = $this->competidorRepo->createCompetidor([
                    'nombres' => $nombreSeparado['nombres'],
                    'apellidos' => $nombreSeparado['apellidos'],
                    'ci' => $fila['ci'],
                    'id_grado' => $gradoObj->id,
                    'id_institucion' => $institucion->id,
                    'id_tutor_legal' => $tutorLegal->id,
                ]);

                $this->inscripcionRepo->createInscripcion([
                    'id_competidor' => $competidor->id,
                    'id_area_nivel' => $areaNivel->id,
                    'id_lista_inscripcion' => $listaInscripcion->id,
                    'gestion' => $olimpiada->gestion
                ]);

                $importados[] = $competidor;
            }

            DB::commit();

            return $this->successResponse(
                'Importación confirmada exitosamente',
                $importados,
                [
                    'imported_rows' => count($importados),
                    'olimpiada_id' => $olimpiada->id,
                    'lista_id' => $listaInscripcion->id
                ],
                201
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("Error en confirmarCsv: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Ocurrió un error al confirmar la importación. Intente nuevamente.',
                [],
                500
            );
        }
    }
}
