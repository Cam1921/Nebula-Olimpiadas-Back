<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Actividad
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $fecha_inicio

 * @property Carbon|null $fecha_fin

 * @property int $id_fase
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Fase $fase
 *
 * @package App\Models
 */
class Actividad extends Model
{
	protected $table = 'actividad';

	protected $casts = [
		'fecha_inicio' => 'date',
		'fecha_fin' => 'date',
		'id_fase' => 'int'
	];

	protected $fillable = [
		'nombre',
		'descripcion',
		'fecha_inicio',
		'hora_inicio_ini',
		'hora_fin_ini',
		'fecha_fin',
		'hora_inicio_fin',
		'hora_fin_fin',
		'estado_publicado',
		'id_fase'
	];

	public function fase()
	{
		return $this->belongsTo(Fase::class, 'id_fase');
	}
}
