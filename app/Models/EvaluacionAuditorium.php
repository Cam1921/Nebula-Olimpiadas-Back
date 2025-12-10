<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EvaluacionAuditorium
 * 
 * @property int $id
 * @property int $id_evaluacion
 * @property int $evaluador_id
 * @property string $cambios
 * @property string|null $ip
 * @property Carbon|null $created_at
 * @property string|null $accion
 * @property string|null $motivo
 *
 * @package App\Models
 */
class EvaluacionAuditorium extends Model
{
	protected $table = 'evaluacion_auditoria';
	public $timestamps = false;

	protected $casts = [
		'id_evaluacion' => 'int',
		'evaluador_id' => 'int',
		'cambios' => 'array'
	];

	protected $fillable = [
		'id_evaluacion',
		'evaluador_id',
		'cambios',
		'ip',
		'accion',
		'motivo'
	];
}
