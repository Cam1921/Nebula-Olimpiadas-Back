<?php

namespace App\Services;

use App\Repositories\TutorRepository;
use App\Traits\NormalizeStringTrait;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
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
     * PREVIEW: Validar CSV y almacenar temporalmente en Redis
     */
    public function previewCsv($file)
    {
        if (!$file) {
            return $this->errorResponse(
                'file not found',
                'Archivo no encontrado o no es un CSV válido',
                [['field' => 'archivo', 'error' => 'Archivo inválido']],
                400,

            );
        }

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        $headers = array_map(fn($h) => $this->normalizeString($h), $headers);

        $required = ['nombre completo', 'ci', 'contacto tutor legal', 'unidad educativa', 'departamento', 'grado', 'area', 'nivel'];
        $optional = ['contacto tutor academico'];

        $required_normalized = array_map(fn($h) => $this->normalizeString($h), $required);
        $optional_normalized = array_map(fn($h) => $this->normalizeString($h), $optional);

        $missing_required = array_diff($required_normalized, $headers);
        if (!empty($missing_required)) {
            return $this->errorResponse(
                'requered headers missing',
                'Encabezados requeridos faltantes',
                [['field' => 'headers', 'error' => implode(', ', $missing_required)]],
                400,
                ['found_headers' => $headers]
            );
        }


        $missing_optional = array_diff($optional_normalized, $headers);
        $warnings = [];
        if (!empty($missing_optional)) {
            $warnings[] = ['field' => 'headers', 'warning' => 'Opcionales faltantes: ' . implode(', ', $missing_optional)];
        }


        $expectedHeaders = array_merge($required_normalized, $optional_normalized);
        $unexpected = array_diff($headers, $expectedHeaders);


        if (!empty($unexpected)) {
            return $this->errorResponse(
                'invalid headers',
                'El archivo contiene encabezados no válidos o desconocidos',
                [['field' => 'headers', 'error' => implode(', ', $unexpected)]],
                400,
                ['found_headers' => $headers]
            );
        }
        $validos = [];
        $errores = [];
        $filaIndex = 1;
        $olimpiada = $this->olimpiadaRepo->getOlimpiadaActiva();
        $import_id = (string) Str::uuid();

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $filaIndex++;
            $fila = [];
            foreach ($headers as $i => $colName) {
                $fila[$colName] = isset($data[$i]) ? trim($data[$i]) : null;
            }

            $filaErrores = [];
            foreach ($required_normalized as $campo) {
                if (empty($fila[$campo])) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => $campo, 'error' => "El campo '$campo' no puede estar vacío"];
                }
            }

            $gradoObj = $this->gradoRepo->findByNombre($fila['grado']);
            $areaObj = $this->areaRepo->findByNombre($fila['area']);
            $nivelObj = $this->nivelRepo->findByNombre($fila['nivel']);

            $telefonos = [
                $fila['contacto tutor legal'] ?? null,
                $fila['contacto tutor academico'] ?? null
            ];
            foreach ($telefonos as $tel) {
                if ($tel && !preg_match('/^\+?\d[\d\s\-]{6,14}\d$/', $tel)) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'contacto tutor', 'error' => "El teléfono '$tel' no es válido. Debe tener entre 6 y 14 dígitos."];
                }
            }


            if (!$gradoObj)
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'grado', 'error' => "El grado '{$fila['grado']}' no existe"];
            if (!$areaObj)
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'area', 'error' => "El área '{$fila['area']}' no existe"];
            if (!$nivelObj)
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'nivel', 'error' => "El nivel '{$fila['nivel']}' no existe"];

            if ($gradoObj && $nivelObj && !$this->gradoRepo->isGradoEnNivel($gradoObj->id, $nivelObj->id)) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'grado/nivel', 'error' => "El grado '{$fila['grado']}' no corresponde al nivel '{$fila['nivel']}'"];
            }

            if ($areaObj && $nivelObj) {
                $areaNivel = $this->areaNivelRepo->findAreaNivel($areaObj->id, $nivelObj->id, $olimpiada->id);
                if (!$areaNivel) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'area/nivel', 'error' => "No existe relación área-nivel para la olimpiada"];
                }
            }
            $yaexiste = false;
            if ($areaObj && $nivelObj) {
                $yaexiste = $this->inscripcionRepo->existeInscripcion($fila['ci'], $areaObj->id, $nivelObj->id, $olimpiada->id);
                if ($yaexiste) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'ci', 'error' => "El competidor con '{$fila['ci']}' ya está registrado en esta área y nivel"];
                }
            }

            // Guardar en tabla temporal
            DB::table('import_temp')->insert([
                'import_id' => $import_id,
                'fila' => $filaIndex,
                'datos' => empty($filaErrores) ? json_encode($fila) : null,
                'errores' => empty($filaErrores) ? null : json_encode($filaErrores),
                'created_at' => now()
            ]);

            if (empty($filaErrores)) {
                $validos[] = ['row' => $filaIndex, 'attributes' => $fila];
            } else {
                $errores = array_merge($errores, $filaErrores);
            }
        }

        fclose($handle);

        $meta = [
            'import_id' => $import_id,
            'total_rows' => count($validos) + count($errores),
            'valid_rows' => count($validos),
            'invalid_rows' => count($errores)
        ];

        return [
            'status' => empty($errores) ? 'success' : 'error',
            'message' => empty($errores) ? 'Validado con exito' : 'Validado con errores',
            'code' => empty($errores) ? 200 : 422,
            'data' => array_slice($validos, 0, 50),
            'errors' => array_slice($errores, 0, 50),
            'meta' => $meta,
            'warnings' => $warnings,
        ];
    }

    /**
     * Obtener errores por import_id
     */
    public function getErroresCsv(string $import_id)
    {
        $filas = DB::table('import_temp')
            ->where('import_id', $import_id)
            ->whereNotNull('errores')
            ->get();

        $errores = [];
        foreach ($filas as $fila) {
            $rowErrores = json_decode($fila->errores, true);
            if ($rowErrores) {
                $errores = array_merge($errores, $rowErrores);
            }
        }
        return $errores;
    }

    /**
     * Confirmar importación
     */
    public function confirmarCsvImportId(string $import_id)
    {
        $filasValidas = DB::table('import_temp')
            ->where('import_id', $import_id)
            ->whereNotNull('datos')
            ->get();

        if ($filasValidas->isEmpty()) {
            return $this->errorResponse('not found rows', 'No hay filas válidas para importar', [], 422);
        }

        DB::beginTransaction();
        try {
            $olimpiada = $this->olimpiadaRepo->getOlimpiadaActiva();
            $listaInscripcion = $this->listaInscripcionRepo->firstOrCreateLista($olimpiada->id);

            $importados = [];

            foreach ($filasValidas as $fila) {
                $f = json_decode($fila->datos, true);

                $gradoObj = $this->gradoRepo->findByNombre($f['grado']);
                $areaObj = $this->areaRepo->findByNombre($f['area']);
                $nivelObj = $this->nivelRepo->findByNombre($f['nivel']);
                $areaNivel = $this->areaNivelRepo->findAreaNivel($areaObj->id, $nivelObj->id, $olimpiada->id);

                $tutorLegal = $this->tutorRepo->firstOrCreatePersona(['ci' => '', 'nombres' => '', 'apellidos' => '', 'telefono' => $f['contacto tutor legal'], 'email' => '']);
                $tutorAcademico = null;
                if ($f['contacto tutor academico'] ?? null) {
                    $tutorAcademico = $this->tutorRepo->firstOrCreatePersona(['ci' => '', 'nombres' => '', 'apellidos' => '', 'telefono' => $f['contacto tutor academico'], 'email' => '']);
                }

                $institucion = $this->institucionRepo->firstOrCreateInstitucion([
                    'nombre_institucion' => $f['unidad educativa'],
                    'departamento_institucion' => $f['departamento'],
                    'municipio_institucion' => ''
                ]);

                $nombreSeparado = separarNombreCompleto($f['nombre completo']);

                $competidor = $this->competidorRepo->createCompetidor([
                    'nombres' => $nombreSeparado['nombres'],
                    'apellidos' => $nombreSeparado['apellidos'],
                    'ci' => $f['ci'],
                    'id_grado' => $gradoObj->id,
                    'id_institucion' => $institucion->id,
                    'id_tutor_legal' => $tutorLegal->id,
                ]);

                $this->inscripcionRepo->createInscripcion([
                    'id_competidor' => $competidor->id,
                    'id_area_nivel' => $areaNivel->id,
                    'id_lista_inscripcion' => $listaInscripcion->id,
                    'id_tutor_academico' => $tutorAcademico->id ?? null,
                    'gestion' => $olimpiada->gestion
                ]);
                $competidorImportado = [
                    'ci' => $f['ci'],
                    'nombres' => $f['nombre completo'],
                    'grado' => $f['grado'],
                    'area' => $f['area'],
                    'nivel' => $f['nivel'],
                    'unidad educativa' => $f['unidad educativa'],
                    'departamento' => $f['departamento'],
                    'contacto tutor legal' => $f['contacto tutor legal'],
                    'contacto tutor academico' => $f['contacto tutor academico'] ?? null
                ];
                $importados[] = $competidorImportado;
            }

            DB::commit();

            // Limpiar tabla temporal
            DB::table('import_temp')->where('import_id', $import_id)->delete();

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
            \Log::error("Error en confirmarCsvImportId: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse('server error', 'Ocurrió un error al confirmar la importación', [], 500);
        }
    }

}
