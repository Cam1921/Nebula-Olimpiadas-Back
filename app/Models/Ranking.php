<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Ranking
 * 
 * @property int $id
 * @property int $id_fase
 * @property int $id_inscripcion
 * @property int|null $puesto_oficial
 * @property string|null $estado_final
 * @property float|null $puntaje_total
 * @property string|null $observacion
 * @property Carbon|null $publicado_en
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Fase $fase
 * @property Inscripcion $inscripcion
 *
 * @package App\Models
 */
class Ranking extends Model
{
	protected $table = 'ranking';

	protected $casts = [
		'id_fase' => 'int',
		'id_inscripcion' => 'int',
		'puesto_oficial' => 'int',
		'puntaje_total' => 'float',
		'publicado_en' => 'datetime'
	];

	protected $fillable = [
		'id_fase',
		'id_inscripcion',
		'puesto_oficial',
		'estado_final',
		'puntaje_total',
		'observacion',
		'publicado_en'
	];

	public function fase()
	{
		return $this->belongsTo(Fase::class, 'id_fase');
	}

	public function inscripcion()
	{
		return $this->belongsTo(Inscripcion::class, 'id_inscripcion');
	}
}
