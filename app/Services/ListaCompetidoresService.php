<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Repositories\TutorCompetidorRepository;
use App\Repositories\InstitucionRepository;
use App\Repositories\CompetidorRepository;
use App\Repositories\AreaRepository;
use App\Repositories\NivelRepository;
use App\Repositories\AreaNivelRepository;
use App\Repositories\InscripcionRepository;

class ListaCompetidoresService
{
    protected $tutorRepo;
    protected $institucionRepo;
    protected $competidorRepo;
    protected $areaRepo;
    protected $nivelRepo;
    protected $areaNivelRepo;
    protected $inscripcionRepo;

    public function __construct(
        TutorCompetidorRepository $tutorRepo,
        InstitucionRepository $institucionRepo,
        CompetidorRepository $competidorRepo,
        AreaRepository $areaRepo,
        NivelRepository $nivelRepo,
        AreaNivelRepository $areaNivelRepo,
        InscripcionRepository $inscripcionRepo
    ) {
        $this->tutorRepo = $tutorRepo;
        $this->institucionRepo = $institucionRepo;
        $this->competidorRepo = $competidorRepo;
        $this->areaRepo = $areaRepo;
        $this->nivelRepo = $nivelRepo;
        $this->areaNivelRepo = $areaNivelRepo;
        $this->inscripcionRepo = $inscripcionRepo;
    }

    public function importarCsv($file)
    {
        $handle = fopen($file, 'r');
        fgetcsv($handle, 1000, ',');

        $importados = [];
        DB::beginTransaction();

        try {
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                [
                    $nombreCompleto,
                    $ci,
                    $nombreTutor,
                    $telefonoTutor,
                    $unidadEducativa,
                    $departamento,
                    $grado,
                    $area,
                    $nivel
                ] = $data;

                // 1. Tutor
                $tutor = $this->tutorRepo->firstOrCreateTutor($nombreTutor, $telefonoTutor);

                // 2. Institución
                $institucion = $this->institucionRepo->firstOrCreateInstitucion($unidadEducativa, $departamento);

                // 3. Competidor
                $competidor = $this->competidorRepo->createCompetidor([
                    'nombre_completo' => $nombreCompleto,
                    'ci' => $ci,
                    'grado' => $grado,
                    'id_institucion' => $institucion->id,
                    'id_tutor' => $tutor->id,
                ]);

                // 4. Área y Nivel
                $areaObj = $this->areaRepo->firstOrCreateArea($area);
                $nivelObj = $this->nivelRepo->firstOrCreateNivel($nivel);
                $areaNivel = $this->areaNivelRepo->firstOrCreateAreaNivel($areaObj->id, $nivelObj->id);


                // 5. Inscripción
                $this->inscripcionRepo->createInscripcion($competidor->id, $areaNivel->id, date('Y'));

                // Guardar datos importados
                $importados[] = [
                    'id' => $competidor->id,
                    'nombre_completo' => $competidor->nombre_completo,
                    'ci' => $competidor->ci,
                    'institucion' => $institucion->nombre_institucion,
                    'tutor' => $tutor->nombre_completo,
                    'area' => $areaObj->nombre_area,
                    'nivel' => $nivelObj->nombre_nivel,
                ];
            }

            $areas = $this->areaRepo->getAllAreas();
            $niveles = $this->nivelRepo->getAllNiveles();

            DB::commit();
            return ['success' => true, 'importados' => $importados, 'areas' => $areas, 'niveles' => $niveles];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public function listarCompetidores($filters, $perPage = 10)
    {
        $idArea = $filters['area_id'] ?? null;
        $idNivel = $filters['nivel_id'] ?? null;

        return $this->competidorRepo->getCompetidores($idArea, $idNivel, $perPage);
    }
}
