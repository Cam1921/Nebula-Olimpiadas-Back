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
 * @property string|null $descripción
 * @property string $estado
 * @property bool $respeto
 * @property bool $integridad
 * @property bool $puntualidad
 * @property int $id_inscripcion
 * @property int $id_fase
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Inscripcion $inscripcion
 * @property Fase $fase
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
		'id_fase' => 'int'
	];

	protected $fillable = [
		'nota',
		'descripción',
		'estado',
		'respeto',
		'integridad',
		'puntualidad',
		'id_inscripcion',
		'id_fase'
	];

	public function inscripcion()
	{
		return $this->belongsTo(Inscripcion::class, 'id_inscripcion');
	}

	public function fase()
	{
		return $this->belongsTo(Fase::class, 'id_fase');
	}
}
