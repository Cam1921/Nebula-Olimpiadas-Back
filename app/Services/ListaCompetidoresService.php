<?php

namespace App\Services;

use App\Repositories\TutorRepository;
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
    private function normalizeHeader($header)
    {
        // quitar espacios de izquierda y derecha
        $header = trim($header);

        // convertir a minúsculas
        $header = mb_strtolower($header);

        // quitar acentos y tildes
        $header = \Illuminate\Support\Str::ascii($header);

        // reemplazar múltiples espacios internos, guiones o tabs por un solo espacio
        $header = preg_replace('/[\s\-]+/', ' ', $header);

        // volver a recortar espacios finales
        $header = trim($header);

        return $header;
    }
    public function importarCsv($file)
    {
        if (!$file) {
            return ['status' => 'error', 'message' => 'Archivo no encontrado o inválido'];
        }

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle, 1000, ',');

        // Normalizar encabezados del CSV
        $headers = array_map(fn($h) => $this->normalizeHeader($h), $headers);

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
        $required_normalized = array_map(fn($h) => $this->normalizeHeader($h), $required);

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
        $optional_normalized = array_map(fn($h) => $this->normalizeHeader($h), $optional);

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

                    // --- Grado ---
                    $gradoObj = $this->gradoRepo->firstOrCreateGrado($fila['grado']);

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

                    // --- Área y Nivel ---
                    $areaObj = $this->areaRepo->firstOrCreateArea($fila['area']);
                    $nivelObj = $this->nivelRepo->firstOrCreateNivel($fila['nivel']);
                    $areaNivel = $this->areaNivelRepo->firstOrCreateAreaNivel($areaObj->id, $nivelObj->id, $olimpiada->id);

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



