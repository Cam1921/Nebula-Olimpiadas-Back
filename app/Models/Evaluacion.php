<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Evaluacion
 * 
 * @property int $id
 * @property float|null $nota
 * @property string|null $descripcion
 * @property string $estado
 * @property bool $respeto
 * @property bool $integridad
 * @property bool $puntualidad
 * @property int $id_inscripcion
 * @property int $id_fase
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $observacion
 * @property string $estado_confirmacion
 * @property int|null $id_asignacion
 * 
 * @property Inscripcion $inscripcion
 * @property Fase $fase
 * @property Asignacion|null $asignacion
 *
 * @package App\Models
 */
class Evaluacion extends Model
{
	protected $table = 'evaluacion';

	protected $casts = [
		'nota' => 'float',
		'respeto' => 'bool',
		'integridad' => 'bool',
		'puntualidad' => 'bool',
		'id_inscripcion' => 'int',
		'id_fase' => 'int',
		'id_asignacion' => 'int'
	];

	protected $fillable = [
		'nota',
		'descripcion',
		'estado',
		'respeto',
		'integridad',
		'puntualidad',
		'id_inscripcion',
		'id_fase',
		'observacion',
		'estado_confirmacion',
		'id_asignacion'
	];

	public function inscripcion()
	{
		return $this->belongsTo(Inscripcion::class, 'id_inscripcion');
	}

	public function fase()
	{
		return $this->belongsTo(Fase::class, 'id_fase');
	}

	public function asignacion()
	{
		return $this->belongsTo(Asignacion::class, 'id_asignacion');
	}

	public function evaluador()
	{
		return $this->hasOneThrough(
			Persona::class,
			Asignacion::class,
			'id',
			'id',
			'id_asignacion',
			'id_persona'
		);
	}
}
