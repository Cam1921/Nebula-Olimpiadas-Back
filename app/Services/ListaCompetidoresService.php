<?php

namespace App\Services;

use App\Repositories\TutorRepository;
use App\Traits\NormalizeStringTrait;
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

class ListaCompetidoresService
{
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

    use NormalizeStringTrait;

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

    public function importarCsv($file)
    {
        if (!$file) {
            return ['status' => 'error', 'message' => 'Archivo no encontrado o inválido'];
        }

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle, 1000, ',');

        // Normalizar encabezados del CSV
        $headers = array_map(fn($h) => $this->normalizeString($h), $headers);

        // --- Encabezados obligatorios ---
        $required = [
            'nombre completo',
            'ci',
            'contacto tutor legal',
            'unidad educativa',
            'departamento',
            'grado',
            'area',
            'nivel'
        ];
        $required_normalized = array_map(fn($h) => $this->normalizeString($h), $required);

        $missing_required = array_diff($required_normalized, $headers);
        if (!empty($missing_required)) {
            return [
                'status' => 'error',
                'message' => 'Encabezados requeridos faltantes: ' . implode(', ', $missing_required),
                'encontrados' => $headers
            ];
        }

        // --- Encabezados opcionales ---
        $optional = ['contacto tutor academico'];
        $optional_normalized = array_map(fn($h) => $this->normalizeString($h), $optional);

        $missing_optional = array_diff($optional_normalized, $headers);
        $advertencias_encabezado = [];
        if (!empty($missing_optional)) {
            // Advertencia para opcionales faltantes
            $advertencias_encabezado[] = "Opcional(es) faltante(s) en CSV: " . implode(', ', $missing_optional);
        }

        $importados = [];
        $errores = [];
        DB::beginTransaction();

        try {
            $olimpiada = $this->olimpiadaRepo->getOlimpiadaActiva();
            $listaInscripcion = $this->listaInscripcionRepo->firstOrCreateLista($olimpiada->id);

            $filaIndex = 1;
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $filaIndex++;

                try {
                    // Mapear fila según encabezados
                    $fila = [];
                    foreach ($headers as $i => $colName) {
                        $fila[$colName] = isset($data[$i]) ? trim($data[$i]) : null;
                    }

                    // --- Validaciones por fila ---
                    $campos_vacios = [];
                    foreach ($required_normalized as $campo) {
                        if (empty($fila[$campo])) {
                            $campos_vacios[] = $campo;
                        }
                    }

                    // Validación de teléfonos
                    $telefonos = ['contacto tutor legal', 'contacto tutor academico'];
                    foreach ($telefonos as $t) {
                        if (!empty($fila[$t])) {
                            if (!preg_match('/^\+?\d[\d\s\-]{6,14}\d$/', $fila[$t])) {
                                $errores[] = [
                                    'fila' => $filaIndex,
                                    'error' => "Formato inválido de teléfono en '$t': {$fila[$t]}"
                                ];
                            }
                        }
                    }

                    if (!empty($campos_vacios)) {
                        $errores[] = [
                            'fila' => $filaIndex,
                            'error' => 'Campos obligatorios vacíos: ' . implode(', ', $campos_vacios),
                            'cantidad_vacios' => count($campos_vacios)
                        ];
                    }

                    // --- Grado ---
                    $gradoObj = $this->gradoRepo->findByNombre($fila['grado']);
                    if (!$gradoObj) {
                        $errores[] = [
                            'fila' => $filaIndex,
                            'error' => "El grado '{$fila['grado']}' no existe en el sistema"
                        ];
                    }

                    // --- Área ---
                    $areaObj = $this->areaRepo->findByNombre($fila['area']);
                    if (!$areaObj) {
                        $errores[] = [
                            'fila' => $filaIndex,
                            'error' => "El área '{$fila['area']}' no existe en el sistema"
                        ];
                    }

                    // --- Nivel ---
                    $nivelObj = $this->nivelRepo->findByNombre($fila['nivel']);
                    if (!$nivelObj) {
                        $errores[] = [
                            'fila' => $filaIndex,
                            'error' => "El nivel '{$fila['nivel']}' no existe en el sistema"
                        ];
                    }

                    // --- Validar coherencia grado-nivel ---
                    if ($gradoObj && $nivelObj) {
                        $nivelGradoExistente = $this->gradoRepo->isGradoEnNivel($gradoObj->id, $nivelObj->id);
                        if (!$nivelGradoExistente) {
                            $errores[] = [
                                'fila' => $filaIndex,
                                'error' => "Incoherencia: el grado '{$gradoObj->nombre_grado}' no corresponde al nivel '{$nivelObj->nombre_nivel}'"
                            ];
                            continue;
                        }
                    }
                    // --- Relación área-nivel ---
                    $areaNivel = null;
                    if ($areaObj && $nivelObj) { // solo si existen área y nivel
                        $areaNivel = $this->areaNivelRepo->findAreaNivel($areaObj->id, $nivelObj->id, $olimpiada->id);
                        if (!$areaNivel) {
                            $errores[] = [
                                'fila' => $filaIndex,
                                'error' => "No existe relación configurada entre área '{$areaObj->nombre_area}' y nivel '{$nivelObj->nombre_nivel}' en la olimpiada"
                            ];
                        }
                    }

                    // --- Validar duplicado ---
                    $yaExiste = false;
                    if ($areaObj && $nivelObj) {
                        $yaExiste = $this->inscripcionRepo->existeInscripcion($fila['ci'], $areaObj->id, $nivelObj->id, $olimpiada->id);
                        if ($yaExiste) {
                            $errores[] = [
                                'fila' => $filaIndex,
                                'error' => "El competidor con CI '{$fila['ci']}' ya está inscrito en el área '{$areaObj->nombre_area}' y nivel '{$nivelObj->nombre_nivel}'"
                            ];
                        }
                    }

                    // --- Decidir si registrar ---
                    if (!$gradoObj || !$areaObj || !$nivelObj || !$areaNivel || $yaExiste || !empty($campos_vacios)) {
                        // No se registra esta fila, ya que hubo errores
                        continue; // aquí solo saltamos al final de la iteración, después de registrar todos los errores
                    }


                    // --- Tutor Legal ---
                    $tutorLegal = $this->tutorRepo->firstOrCreatePersona([
                        'ci' => '',
                        'nombres' => '',
                        'apellidos' => '',
                        'telefono' => $fila['contacto tutor legal'],
                        'email' => ''
                    ]);

                    // --- Tutor Académico (opcional) ---
                    $tutorAcademico = null;
                    if (!empty($fila['contacto tutor academico'])) {
                        $tutorAcademico = $this->tutorRepo->firstOrCreatePersona([
                            'ci' => '',
                            'nombres' => '',
                            'apellidos' => '',
                            'telefono' => $fila['contacto tutor academico'],
                            'email' => ''
                        ]);
                    }

                    // --- Institución ---
                    $institucion = $this->institucionRepo->firstOrCreateInstitucion([
                        'nombre_institucion' => $fila['unidad educativa'],
                        'departamento_institucion' => $fila['departamento'],
                        'municipio_institucion' => ''
                    ]);



                    // --- Competidor ---
                    $nombreSeparadoCompetidor = separarNombreCompleto($fila['nombre completo']);
                    $competidor = $this->competidorRepo->createCompetidor([
                        'nombres' => $nombreSeparadoCompetidor['nombres'],
                        'apellidos' => $nombreSeparadoCompetidor['apellidos'],
                        'ci' => $fila['ci'],
                        'id_grado' => $gradoObj->id,
                        'id_institucion' => $institucion->id,
                        'id_tutor_legal' => $tutorLegal->id,
                    ]);



                    // --- Inscripción ---
                    $this->inscripcionRepo->createInscripcion([
                        'id_competidor' => $competidor->id,
                        'id_area_nivel' => $areaNivel->id,
                        'id_lista_inscripcion' => $listaInscripcion->id,
                        'id_tutor_academico' => $tutorAcademico?->id,
                        'gestion' => $olimpiada->gestion
                    ]);

                    $importados[] = [
                        'id' => $competidor->id,
                        'nombre_completo' => unirNombreCompleto($nombreSeparadoCompetidor['nombres'], $nombreSeparadoCompetidor['apellidos']),
                        'ci' => $competidor->ci,
                        'institucion' => $institucion->nombre_institucion,
                        'departamento' => $institucion->departamento_institucion,
                        'contacto_tutor_legal' => $tutorLegal->telefono,
                        'area' => $areaObj->nombre_area,
                        'nivel' => $nivelObj->nombre_nivel,
                        'grado' => $gradoObj->nombre_grado
                    ];

                } catch (\Exception $eFila) {
                    $errores[] = [
                        'fila' => $filaIndex,
                        'error' => $eFila->getMessage()
                    ];
                }
            }

            DB::commit();
            $totalInsertados = count($importados);
            return [
                'status' => 'ok',
                'import_id' => (string) \Illuminate\Support\Str::uuid(),
                'insertados' => $totalInsertados,
                'importados' => array_slice($importados, 0, 10),
                'errores' => $errores,
                'advertencias_encabezado' => $advertencias_encabezado
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }


}



