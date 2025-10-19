<?php

namespace App\Services;

use App\Repositories\EquipoRepository;
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

class ImportacionesService
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

    protected $equipoRepo;

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
        OlimpiadaRepository $olimpiadaRepo,
        EquipoRepository $equipoRepo
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
        $this->equipoRepo = $equipoRepo;
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
                400
            );
        }

        set_time_limit(300); // Aumenta el tiempo máximo temporalmente

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        $headers = array_map(fn($h) => $this->normalizeString($h), $headers);

        $required = ['nombre completo', 'ci', 'contacto tutor legal', 'unidad educativa', 'departamento', 'grado', 'area', 'nivel'];
        $optional = ['contacto tutor academico', 'nombre equipo'];
        $required_normalized = array_map(fn($h) => $this->normalizeString($h), $required);
        $optional_normalized = array_map(fn($h) => $this->normalizeString($h), $optional);

        // Validación de encabezados
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

        // Cargar todo en memoria antes del loop
        $grados = $this->gradoRepo->getAllNormalized();
        $areas = $this->areaRepo->getAllNormalized();             // ['area_normalizado' => objeto]
        $niveles = $this->nivelRepo->getAllNormalized();          // ['nivel_normalizado' => objeto]
        $gradoNivel = $this->gradoRepo->getAllGradoNivel();       // ['grado_id-nivel_id' => true]
        $olimpiada = $this->olimpiadaRepo->getOlimpiadaActiva();
        $areaNiveles = $this->areaNivelRepo->getAllByOlimpiada($olimpiada->id); // ['area_id-nivel_id' => true]
        $inscripcionesExistentes = $this->inscripcionRepo->getAllByOlimpiada($olimpiada->id); // ['ci-areaId-nivelId' => true]

        $validos = [];
        $errores = [];
        $filaIndex = 1;
        $import_id = (string) Str::uuid();
        $batchInsert = [];

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $filaIndex++;
            $fila = [];
            foreach ($headers as $i => $colName) {
                $fila[$colName] = isset($data[$i]) ? trim($data[$i]) : null;
            }

            $filaErrores = [];

            // Validar campos requeridos
            foreach ($required_normalized as $campo) {
                if (empty($fila[$campo])) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => $campo, 'error' => "El campo '$campo' no puede estar vacío"];
                }
            }

            // Obtener objetos en memoria
            $gradoObj = $grados[$this->normalizeString($fila['grado'])] ?? null;
            $areaObj = $areas[$this->normalizeString($fila['area'])] ?? null;
            $nivelObj = $niveles[$this->normalizeString($fila['nivel'])] ?? null;


            if (!$gradoObj)
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'grado', 'error' => "El grado '{$fila['grado']}' no existe"];
            if (!$areaObj)
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'area', 'error' => "El área '{$fila['area']}' no existe"];
            if (!$nivelObj)
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'nivel', 'error' => "El nivel '{$fila['nivel']}' no existe"];

            // Validar combinación grado-nivel en memoria
            if ($gradoObj && $nivelObj) {
                $key = $gradoObj->id . '-' . $nivelObj->id;
                if (!isset($gradoNivel[$key])) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'grado/nivel', 'error' => "El grado '{$fila['grado']}' no corresponde al nivel '{$fila['nivel']}'"];
                }
            }

            // Validar combinación área-nivel en memoria
            if ($areaObj && $nivelObj) {
                $key = $areaObj->id . '-' . $nivelObj->id;
                if (!isset($areaNiveles[$key])) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'area/nivel', 'error' => "No existe relación área-nivel para la olimpiada"];
                }
            }

            // Validar duplicados en memoria
            if ($areaObj && $nivelObj) {
                $key = $fila['ci'] . '-' . $areaObj->id . '-' . $nivelObj->id;
                if (isset($inscripcionesExistentes[$key])) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'ci', 'error' => "El competidor con el CI '{$fila['ci']}' ya está registrado en esta área y nivel"];
                }
            }

            // Validar teléfonos
            $telefonos = [$fila['contacto tutor legal'] ?? null, $fila['contacto tutor academico'] ?? null];
            foreach ($telefonos as $tel) {
                if ($tel && !preg_match('/^\+?\d[\d\s\-]{6,14}\d$/', $tel)) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'contacto tutor', 'error' => "El teléfono '$tel' no es válido. Debe tener entre 6 y 14 dígitos."];
                }
            }

            //validar equipo
            if (!empty($fila['nombre equipo']) && $areaObj && $areaObj->nombre_area !== 'Robotica') {
                $filaErrores[] = [
                    'row' => $filaIndex,
                    'field' => 'nombre equipo',
                    'error' => 'Solo se permite asignar equipos para el área de robótica'
                ];
            }

            // Preparar insert masivo
            $batchInsert[] = [
                'import_id' => $import_id,
                'fila' => $filaIndex,
                'datos' => empty($filaErrores) ? json_encode($fila) : null,
                'errores' => empty($filaErrores) ? null : json_encode($filaErrores),
                'created_at' => now()
            ];

            if (empty($filaErrores)) {
                $validos[] = ['row' => $filaIndex, 'attributes' => $fila];
            } else {
                $errores = array_merge($errores, $filaErrores);
            }

            // Insertar en lotes de 500 filas
            if (count($batchInsert) >= 500) {
                DB::table('import_temp')->insert($batchInsert);
                $batchInsert = [];
            }
        }

        // Insertar las filas restantes
        if (!empty($batchInsert)) {
            DB::table('import_temp')->insert($batchInsert);
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
        ini_set('max_execution_time', 300); // 5 minutos
        ini_set('memory_limit', '512M'); // por si hay miles de filas

        $chunkSize = 300; // tamaño de bloque

        $query = DB::table('import_temp')
            ->where('import_id', $import_id)
            ->whereNotNull('datos')
            ->orderBy('fila');

        if (!$query->exists()) {
            return $this->errorResponse('not found rows', 'No hay filas válidas para importar', [], 422);
        }

        $olimpiada = $this->olimpiadaRepo->getOlimpiadaActiva();
        $listaInscripcion = $this->listaInscripcionRepo->firstOrCreateLista($olimpiada->id);

        // --- Precargar entidades ---
        $grados = $this->gradoRepo->getAll()->keyBy(fn($g) => mb_strtolower(trim($g->nombre_grado)));
        $areas = $this->areaRepo->getAll()->keyBy(fn($a) => mb_strtolower(trim($a->nombre_area)));
        $niveles = $this->nivelRepo->getAll()->keyBy(fn($n) => mb_strtolower(trim($n->nombre_nivel)));

        $institucionesCache = [];
        $tutoresCache = [];
        $importados = [];
        $totalImportados = 0;

        $query->chunk($chunkSize, function ($filas) use (&$grados, &$areas, &$niveles, &$institucionesCache, &$tutoresCache, &$importados, &$totalImportados, $olimpiada, $listaInscripcion) {
            // 🧩 Procesar sin transacción global (cada inserción es independiente)
            foreach ($filas as $fila) {
                try {
                    $f = json_decode($fila->datos, true);
                    if (!$f)
                        continue;

                    $gradoKey = mb_strtolower(trim($f['grado']));
                    $areaKey = mb_strtolower(trim($f['area']));
                    $nivelKey = mb_strtolower(trim($f['nivel']));

                    // --- Grado, área, nivel (cacheados) ---
                    $gradoObj = $grados[$gradoKey] ??= $this->gradoRepo->firstOrCreate(['nombre_grado' => $f['grado']]);
                    $areaObj = $areas[$areaKey] ??= $this->areaRepo->firstOrCreate(['nombre_area' => $f['area']]);
                    $nivelObj = $niveles[$nivelKey] ??= $this->nivelRepo->firstOrCreate(['nombre_nivel' => $f['nivel']]);

                    $areaNivel = $this->areaNivelRepo->findAreaNivel($areaObj->id, $nivelObj->id, $olimpiada->id);

                    // --- Tutores ---
                    $tutorLegalKey = trim($f['contacto tutor legal']);
                    $tutorLegal = $tutoresCache[$tutorLegalKey] ??= $this->tutorRepo->firstOrCreatePersona([
                        'telefono' => $tutorLegalKey,
                        'ci' => '',
                        'nombres' => '',
                        'apellidos' => '',
                        'email' => ''
                    ]);

                    //--- Tutor académico (opcional) ---
                    $tutorAcademico = null;
                    if (!empty($f['contacto tutor academico'])) {
                        $tutorAcademicoKey = trim($f['contacto tutor academico']);
                        $tutorAcademico = $tutoresCache[$tutorAcademicoKey] ??= $this->tutorRepo->firstOrCreatePersona([
                            'telefono' => $tutorAcademicoKey,
                            'ci' => '',
                            'nombres' => '',
                            'apellidos' => '',
                            'email' => ''
                        ]);
                    }
                    //---Equipo (opcional) ---  --- IGNORE ---
                    $Equipo = null;

                    if (!empty($f['nombre equipo'] ?? null)) {
                        $EquipoKey = trim($f['nombre equipo']);
                        \Log::info("Intentando crear equipo: " . $EquipoKey);

                        $Equipo = $this->equipoRepo->firstOrCreate([
                            'nombre_equipo' => $EquipoKey,
                        ]);

                        \Log::info("Equipo creado/encontrado: " . ($Equipo->id ?? 'sin id'));
                    }


                    // --- Institución ---
                    $instKey = mb_strtolower(trim($f['unidad educativa'])) . '|' . mb_strtolower(trim($f['departamento']));
                    $institucion = $institucionesCache[$instKey] ??= $this->institucionRepo->firstOrCreateInstitucion([
                        'nombre_institucion' => $f['unidad educativa'],
                        'departamento_institucion' => $f['departamento'],
                        'municipio_institucion' => ''
                    ]);

                    // --- Competidor ---
                    $nombreSeparado = separarNombreCompleto($f['nombre completo']);

                    $competidor = $this->competidorRepo->createCompetidor([
                        'nombres' => $nombreSeparado['nombres'],
                        'apellidos' => $nombreSeparado['apellidos'],
                        'ci' => $f['ci'],
                        'id_grado' => $gradoObj->id,
                        'id_institucion' => $institucion->id,
                        'id_tutor_legal' => $tutorLegal->id,
                        'id_equipo' => $Equipo->id ?? null,
                    ]);

                    $this->inscripcionRepo->createInscripcion([
                        'id_competidor' => $competidor->id,
                        'id_area_nivel' => $areaNivel->id,
                        'id_lista_inscripcion' => $listaInscripcion->id,
                        'id_tutor_academico' => $tutorAcademico->id ?? null,
                        'gestion' => $olimpiada->gestion
                    ]);

                    $importados[] = [
                        'ci' => $f['ci'],
                        'nombres' => $f['nombre completo'],
                        'grado' => $f['grado'],
                        'area' => $f['area'],
                        'nivel' => $f['nivel'],
                        'unidad educativa' => $f['unidad educativa'],
                        'departamento' => $f['departamento'],
                        'contacto tutor legal' => $f['contacto tutor legal'],
                        'contacto tutor academico' => $f['contacto tutor academico'] ?? null,
                        'nombre equipo' => $f['nombre equipo'] ?? null
                    ];

                    $totalImportados++;
                } catch (\Throwable $e) {
                    \Log::error("Error al procesar fila CSV: " . $e->getMessage(), [
                        'fila' => $fila->fila,
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }
        });

        // Limpiar tabla temporal
        DB::table('import_temp')->where('import_id', $import_id)->delete();

        return $this->successResponse(
            'Importación confirmada exitosamente',
            $importados,
            [
                'imported_rows' => $totalImportados,
                'olimpiada_id' => $olimpiada->id,
                'lista_id' => $listaInscripcion->id
            ],
            201
        );
    }



}
