<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Clasificacion
 * 
 * @property int $id
 * @property float|null $puntaje
 * @property string|null $estado_clasificacion
 * @property int|null $posicion
 * @property int $id_inscrito
 * @property Carbon $fecha_registro
 * 
 * @property Inscripcion $inscripcion
 *
 * @package App\Models
 */
class Clasificacion extends Model
{
	protected $table = 'clasificacion';
	public $timestamps = false;

	protected $casts = [
		'puntaje' => 'float',
		'posicion' => 'int',
		'id_inscrito' => 'int',
		'fecha_registro' => 'datetime'
	];

	protected $fillable = [
		'puntaje',
		'estado_clasificacion',
		'posicion',
		'id_inscrito',
		'fecha_registro'
	];

	public function inscripcion()
	{
		return $this->belongsTo(Inscripcion::class, 'id_inscrito');
	}
}
