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
 * @property string $descripción
 * @property string $estado
 * @property bool $respeto
 * @property bool $integridad
 * @property bool $puntualidad
 * @property int $id_inscripcion
 * @property int $id_ompiada_fase
 * @property int $id_evaluador
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Inscripcion $inscripcion
 * @property OlimpiadaFase $olimpiada_fase
 * @property Persona $persona
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
		'id_ompiada_fase' => 'int',
		'id_evaluador' => 'int'
	];

	protected $fillable = [
		'nota',
		'descripción',
		'estado',
		'respeto',
		'integridad',
		'puntualidad',
		'id_inscripcion',
		'id_ompiada_fase',
		'id_evaluador'
	];

	public function inscripcion()
	{
		return $this->belongsTo(Inscripcion::class, 'id_inscripcion');
	}

	public function olimpiada_fase()
	{
		return $this->belongsTo(OlimpiadaFase::class, 'id_ompiada_fase');
	}

	public function persona()
	{
		return $this->belongsTo(Persona::class, 'id_evaluador');
	}
}
