<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Asignacion;
use App\Models\Persona;
use App\Models\PersonaArea;
use App\Models\User;
use App\Repositories\AreaNivelRepository;
use App\Repositories\AreaRepository;
use App\Repositories\AsignacionAreaRepository;
use App\Repositories\AsignacionRepository;
use App\Repositories\InvitacionRepository;
use App\Repositories\NivelRepository;
use App\Repositories\OlimpiadaRepository;
use App\Repositories\PersonaRepository;
use App\Traits\ApiResponseTrait;
use App\Traits\NormalizeStringTrait;
use Illuminate\Support\Facades\Hash;
use DB;
use Illuminate\Support\Facades\Log;
use Str;
class ImportarEvaluadoresService
{
    use NormalizeStringTrait;
    use ApiResponseTrait;

    protected $personaRepository;
    protected $areaNivelRepository;
    protected $areaRepository;
    protected $nivelRepository;
    protected $olimpiadaRepository;
    protected $personaService;
    protected $asignacionAreaRepository;

    protected $asignacionRepository;


    public function __construct(
        PersonaRepository $personaRepository,
        AreaNivelRepository $areaNivelRepository,
        AreaRepository $areaRepository,
        NivelRepository $nivelRepository,
        AsignacionAreaRepository $asignacionAreaRepository,
        OlimpiadaRepository $olimpiadaRepository,
        AsignacionRepository $asignacionRepository,
        PersonaService $personaService
    ) {
        $this->personaRepository = $personaRepository;
        $this->areaNivelRepository = $areaNivelRepository;
        $this->areaRepository = $areaRepository;
        $this->nivelRepository = $nivelRepository;
        $this->asignacionAreaRepository = $asignacionAreaRepository;
        $this->olimpiadaRepository = $olimpiadaRepository;
        $this->asignacionRepository = $asignacionRepository;
        $this->personaService = $personaService;
    }

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

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle, 1000, ',');
        $headers = array_map(fn($h) => $this->normalizeString($h), $headers);

        $required = ['nombre', 'apellidos', 'correo', 'telefono', 'ci', 'area'];
        $optional = ['nivel'];
        $required_normalized = array_map(fn($h) => $this->normalizeString($h), $required);
        $optional_normalized = array_map(fn($h) => $this->normalizeString($h), $optional);

        // Validación de encabezados
        $missing_required = array_diff($required_normalized, $headers);
        if (!empty($missing_required)) {
            return $this->errorResponse(
                'required headers missing',
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

        // ===============================
        // Datos de referencia
        // ===============================
        $areas = $this->areaRepository->getAllNormalized();
        $niveles = $this->nivelRepository->getAllNormalized();
        $asignacionesAreas = $this->asignacionAreaRepository->AsignacionAreaAll('evaluador');
        $olimpiada = $this->olimpiadaRepository->getOlimpiadaActiva();
        $areaNiveles = $this->areaNivelRepository->getAllByOlimpiada($olimpiada->id); // ['area_id-nivel_id' => true]
        $id_area_nivel = $this->areaNivelRepository->getIdAllOlimpiada($olimpiada->id);
        $AreaNivelesId = $this->asignacionRepository->getAllRolAreaNivelIds('evaluador');

        $validos = [];
        $errores = [];
        $filaIndex = 1;
        $countInvalid = 0;
        $import_id = (string) Str::uuid();
        $batchInsert = [];

        $evaluadores = Persona::whereHas('rols', function ($query) {
            $query->where('nombre', 'evaluador');
        })->get();

        $seenFilas = [];
        $seenPersonaArea = [];
        $areaNivelKey = [];

        $seenCorreo = $evaluadores->pluck('email')->filter()->mapWithKeys(fn($email) => [$email => true])->toArray();
        $seenCi = $evaluadores->pluck('ci')->filter()->mapWithKeys(fn($ci) => [$ci => true])->toArray();
        $seenTelefono = $evaluadores->pluck('telefono')->filter()->mapWithKeys(fn($tel) => [$tel => true])->toArray();
        $areaContador = PersonaArea::with('area')
            ->whereHas('persona', fn($q) => $q->whereHas('rols', fn($qr) => $qr->where('nombre', 'evaluador')))
            ->get()
            ->groupBy(fn($pa) => $pa->area->id) // ahora agrupamos por id de área
            ->map(fn($items) => count($items))
            ->toArray();
        $areaMaximos = Area::pluck('cantidad_evaluadores', 'id')->toArray();
        $seenFilas = Asignacion::whereHas(
            'persona',
            fn($q) =>
            $q->whereHas('rols', fn($qr) => $qr->where('nombre', 'evaluador'))
        )->get()
            ->mapWithKeys(function ($asignacion) {
                $persona = $asignacion->persona;
                $area = $asignacion->area_nivel->area->nombre_area;    // Asumiendo relaciones definidas
                $nivel = $asignacion->area_nivel->nivel->nombre_nivel;
                $key = "{$persona->nombres}-{$persona->apellidos}-{$persona->email}-{$persona->telefono}-{$persona->ci}-{$area}-{$nivel}";
                return [$key => true];
            })
            ->toArray();
        $seenPersonaArea = PersonaArea::whereHas(
            'persona',
            fn($q) =>
            $q->whereHas('rols', fn($qr) => $qr->where('nombre', 'evaluador'))
        )->get()
            ->mapWithKeys(function ($asignacion) {
                $persona = $asignacion->persona;
                $area = $asignacion->area->nombre_area;    // Asumiendo relaciones definidas
    
                $key = "{$persona->nombres}-{$persona->apellidos}-{$persona->email}-{$persona->telefono}-{$persona->ci}-{$area}";
                return [$key => true];
            })
            ->toArray();

        $seenAreaNivel = Asignacion::whereHas(
            'persona',
            fn($q) =>
            $q->whereHas('rols', fn($qr) => $qr->where('nombre', 'evaluador'))
        )->get()
            ->mapWithKeys(function ($asignacion) {
                $area = $asignacion->area_nivel->area->nombre_area;
                $nivel = $asignacion->area_nivel->nivel->nombre_nivel;
                $key = "{$area}-{$nivel}";
                return [$key => true];
            })
            ->toArray();
        // Control de duplicados dentro del CSV        
        Log::debug('Iniciando control de duplicados', [$seenPersonaArea]);
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $filaIndex++;
            $fila = [];
            foreach ($headers as $i => $colName) {
                $fila[$colName] = isset($data[$i]) ? trim($data[$i]) : null;
            }

            $filaErrores = [];

            // ===============================
            // Validaciones básicas
            // ===============================
            foreach ($required_normalized as $campo) {
                if (empty($fila[$campo])) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => $campo, 'error' => "El campo '$campo' no puede estar vacío"];
                }
            }

            if (!empty($fila['nombre']) && strlen(trim($fila['nombre'])) < 3) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'nombre', 'error' => "El nombre debe tener al menos 3 caracteres"];
            }

            if (!empty($fila['apellidos']) && strlen(trim($fila['apellidos'])) < 3) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'apellidos', 'error' => "Los apellidos deben tener al menos 3 caracteres"];
            }

            if (!empty($fila['correo'])) {
                if (!filter_var($fila['correo'], FILTER_VALIDATE_EMAIL) || strlen($fila['correo']) > 50) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'correo', 'error' => "Correo inválido o mayor a 50 caracteres"];
                }
            }

            if (!empty($fila['telefono']) && !preg_match('/^[67]\d{7}$/', $fila['telefono'])) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'telefono', 'error' => "Teléfono inválido, debe tener 8 dígitos y comenzar con 6 o 7"];
            }

            if (!empty($fila['ci']) && !preg_match('/^\d{6,10}$/', $fila['ci'])) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'ci', 'error' => "CI inválido, debe ser numérico entre 6 y 10 dígitos"];
            }


            // ===============================
            // Validación área y nivel
            // ===============================
            $areaObj = $areas[$this->normalizeString($fila['area'])] ?? null;
            $nivelObj = null;
            if (!empty($fila['nivel'])) {
                $nivelKey = $this->normalizeString($fila['nivel']);
                $nivelObj = $niveles[$nivelKey] ?? null;
                if (!$nivelObj) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'nivel', 'error' => "El nivel '{$fila['nivel']}' no existe"];
                }
            }
            if (!$areaObj)
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'area', 'error' => "El área '{$fila['area']}' no existe"];

            if ($areaObj && $nivelObj) {
                $key = $areaObj->id . '-' . $nivelObj->id;
                if (!isset($areaNiveles[$key])) {
                    $filaErrores[] = ['row' => $filaIndex, 'field' => 'area/nivel', 'error' => "No existe relación área-nivel para la olimpiada"];
                } else {
                    $id = $id_area_nivel[$key];
                    if (in_array($id, $AreaNivelesId)) {
                        $filaErrores[] = ['row' => $filaIndex, 'field' => 'asignacion', 'error' => "Ya existe una asignación para el área-nivel '$areaObj->nombre_area-$nivelObj->nombre_nivel'"];
                    }
                }
            }

            if (!$areaObj) {
                $filaErrores[] = ['row' => $filaIndex, 'field' => 'area', 'error' => "El área '{$fila['area']}' no existe"];
            }
            // ===============================
            // Normalización de datos clave
            // ===============================
            $nombre = strtolower(trim($fila['nombre'] ?? ''));
            $apellidos = strtolower(trim($fila['apellidos'] ?? ''));
            $correo = strtolower(trim($fila['correo'] ?? ''));
            $ci = $this->normalizeString($fila['ci'] ?? '');
            $telefono = $this->normalizeString($fila['telefono'] ?? '');
            $area = strtolower(trim($fila['area'] ?? ''));
            $nivel = strtolower(trim($fila['nivel'] ?? ''));
            $id_area = $areaObj ? $areaObj->id : null;

            $filaClave = "{$nombre}-{$apellidos}-{$correo}-{$telefono}-{$ci}-{$area}-{$nivel}";
            $personaAreaKey = "{$nombre}-{$apellidos}-{$correo}-{$telefono}-{$ci}-{$area}";
            $areaNivelKey = "{$area}-{$nivel}";

            if (isset($seenFilas[$filaClave])) {
                $filaErrores[] = [
                    'row' => $filaIndex,
                    'field' => 'fila',
                    'error' => 'Fila duplicada: la misma persona ya está asignada al mismo área y nivel en este CSV',
                ];
            } else {
                $seenFilas[$filaClave] = true;
                if (isset($seenPersonaArea[$personaAreaKey])) {
                    if (empty($nivel)) {
                        $filaErrores[] = [
                            'row' => $filaIndex,
                            'field' => 'area',
                            'error' => 'La persona ya está registrada en esta área, debe especificar un nivel distinto',
                        ];
                    } else if (isset($seenAreaNivel[$areaNivelKey])) {
                        $filaErrores[] = [
                            'row' => $filaIndex,
                            'field' => 'area_nivel',
                            'error' => 'Otra persona ya está registrada en el mismo área y nivel',
                        ];

                    } else {
                        if (!empty($nivel)) {
                            $seenAreaNivel[$areaNivelKey] = true;
                        }
                    }

                } else {


                    Log::debug('Correo normalizado:', [$correo]);
                    Log::debug('Contenido de seenCorreo:', $seenCorreo);

                    $duplicados = [];
                    if (!empty($correo) && isset($seenCorreo[$correo]))
                        $duplicados[] = 'correo';
                    if (!empty($ci) && isset($seenCi[$ci]))
                        $duplicados[] = 'CI';
                    if (!empty($telefono) && isset($seenTelefono[$telefono]))
                        $duplicados[] = 'teléfono';

                    if (!empty($duplicados)) {
                        $filaErrores[] = [
                            'row' => $filaIndex,
                            'field' => implode('/', $duplicados),
                            'error' => 'Ya existe registro duplicado en: ' . implode(', ', $duplicados),
                        ];
                    }

                    // Validar área-nivel (solo si no hay errores de duplicados generales)

                    if (isset($seenAreaNivel[$areaNivelKey])) {
                        $filaErrores[] = [
                            'row' => $filaIndex,
                            'field' => 'area_nivel',
                            'error' => 'Otra persona ya está registrada en el mismo área y nivel',
                        ];
                    }


                    // -------------------------
                    // 7 Registrar como visto (siempre, para la siguiente fila)
                    // -------------------------
                    if (!empty($correo))
                        $seenCorreo[$correo] = true;
                    if (!empty($ci))
                        $seenCi[$ci] = true;
                    if (!empty($telefono))
                        $seenTelefono[$telefono] = true;

                    // Registrar área-nivel solo si la fila no tiene errores de duplicado de área-nivel
                    if (!isset($filaErrores) || !in_array('area_nivel', array_column($filaErrores, 'field'))) {
                        if (!empty($nivel))
                            $seenAreaNivel[$areaNivelKey] = true;
                        $seenPersonaArea[$personaAreaKey] = true;
                    }
                }
                if ($id_area) {
                    if (!isset($areaContador[$id_area]))
                        $areaContador[$id_area] = 0;
                    if ($areaContador[$id_area] >= ($areaMaximos[$id_area] ?? PHP_INT_MAX)) {
                        $filaErrores[] = [
                            'row' => $filaIndex,
                            'field' => 'area',
                            'error' => "Se ha alcanzado el límite de evaluadores para el área con ID {$id_area}"
                        ];
                    } else {
                        $areaContador[$id_area]++;
                    }
                }

            }
            $batchInsert[] = [
                'import_id' => $import_id,
                'fila' => $filaIndex,
                'datos' => empty($filaErrores) ? json_encode($fila) : null,
                'errores' => empty($filaErrores) ? null : json_encode($filaErrores),
                'created_at' => now()
            ];

            if (!empty($filaErrores)) {
                $errores = array_merge($errores, $filaErrores);
                $countInvalid++;
                Log::debug("Fila $filaIndex con errores detectados", ['countInvalid' => $countInvalid, 'fila' => $filaErrores]);
            } else {
                $validos[] = $fila;
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
        $totalrows = $filaIndex;
        $invaludrows = $countInvalid;
        $validRows = $totalrows - $countInvalid;
        $meta = [
            'import_id' => $import_id,
            'total_rows' => $totalrows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invaludrows
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




    public function confirmarCsvImportId($import_id)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $query = DB::table('import_temp')
            ->where('import_id', $import_id)
            ->whereNotNull('datos')
            ->orderBy('fila');

        if (!$query->exists()) {
            return $this->errorResponse('not found rows', 'No hay filas válidas para importar', [], 422);
        }

        $filas = $query->get();
        $olimpiada = $this->olimpiadaRepository->getOlimpiadaActiva();
        $areas = $this->areaRepository->getAllNormalized();
        $niveles = $this->nivelRepository->getAllNormalized();
        $id_area_nivel = $this->areaNivelRepository->getIdAllOlimpiada($olimpiada->id);
        $rolEvaluador = DB::table('rol')->where('nombre', 'evaluador')->first();

        $creados = 0;
        $existentes = 0;
        $personasParaCorreo = [];
        DB::beginTransaction();
        try {
            foreach ($filas as $fila) {
                $data = json_decode($fila->datos, true);

                $nombre = trim($data['nombre']);
                $apellidos = trim($data['apellidos']);
                $correo = strtolower(trim($data['correo']));
                $telefono = trim($data['telefono']);
                $ci = trim($data['ci']);
                $areaKey = $this->normalizeString($data['area']);
                $nivelKey = isset($data['nivel']) ? $this->normalizeString($data['nivel']) : null;

                $areaObj = $areas[$areaKey] ?? null;
                $nivelObj = $nivelKey ? ($niveles[$nivelKey] ?? null) : null;

                if (!$areaObj)
                    continue;

                // Buscar persona existente
                $persona = Persona::where('ci', $ci)
                    ->orWhere('email', $correo)
                    ->orWhere('telefono', $telefono)
                    ->first();

                if (!$persona) {
                    // Crear usuario con contraseña aleatoria
                    $password = Str::random(10);
                    $userId = DB::table('users')->insertGetId([
                        'name' => $nombre . ' ' . $apellidos,
                        'email' => $correo,
                        'password' => bcrypt($password),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Crear persona
                    $persona = Persona::create([
                        'ci' => $ci,
                        'nombres' => $nombre,
                        'apellidos' => $apellidos,
                        'telefono' => $telefono,
                        'email' => $correo,
                        'id_usuario' => $userId,
                    ]);

                    // Asignar rol evaluador
                    DB::table('persona_rol')->insert([
                        'id_persona' => $persona->id,
                        'id_rol' => $rolEvaluador->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $personasParaCorreo[] = $persona->id;
                    $creados++;
                } else {
                    $existentes++;
                }


                // Registrar PersonaArea si no existe
                $personaArea = DB::table('persona_area')
                    ->where('id_persona', $persona->id)
                    ->where('id_area', $areaObj->id)
                    ->first();

                if (!$personaArea) {
                    DB::table('persona_area')->insert([
                        'id_persona' => $persona->id,
                        'id_area' => $areaObj->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Si viene con nivel, registrar también en Asignacion
                if ($nivelObj) {
                    $key = "{$areaObj->id}-{$nivelObj->id}";
                    if (isset($id_area_nivel[$key])) {
                        $idAreaNivel = $id_area_nivel[$key];

                        // Evitar duplicados
                        $yaAsignado = DB::table('asignacion')
                            ->where('id_persona', $persona->id)
                            ->where('id_area_nivel', $idAreaNivel)
                            ->exists();

                        if (!$yaAsignado) {
                            DB::table('asignacion')->insert([
                                'id_persona' => $persona->id,
                                'id_area_nivel' => $idAreaNivel,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

            }
            DB::table('import_temp')->where('import_id', $import_id)->delete();
            DB::commit();
            foreach ($personasParaCorreo as $idPersona) {
                try {
                    $this->personaService->enviarCorreoCreacionPassword($idPersona);
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enviar el correo de creación al evaluador con ID: $idPersona", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            return $this->successResponse(

                "Importación completada correctamente",
                [],
                [
                    'nuevos_registrados' => $creados,
                    'personas_existentes' => $existentes,
                ],
                201
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al confirmar importación de evaluadores: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'import_failed',
                'Ocurrió un error durante la importación',
                [['error' => $e->getMessage()]],
                500
            );
        }
    }


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

}