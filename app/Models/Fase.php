<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Fase
 * 
 * @property int $id
 * @property string $nombre
 * @property string $descripcion
 * @property string $estado
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_fin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property time without time zone|null $hora_inicio_ini
 * @property time without time zone|null $hora_fin_ini
 * @property time without time zone|null $hora_inicio_fin
 * @property time without time zone|null $hora_fin_fin
 * 
 * @property Collection|AreaNivel[] $area_nivels
 * @property Collection|Evaluacion[] $evaluacions
 * @property Collection|Actividad[] $actividads
 *
 * @package App\Models
 */
class Fase extends Model
{
	protected $table = 'fase';

	protected $casts = [
		'fecha_inicio' => 'date',
		'fecha_fin' => 'date',

	];

	protected $fillable = [
		'nombre',
		'descripcion',
		'estado',
		'fecha_inicio',
		'fecha_fin',
		'hora_inicio_ini',
		'hora_fin_ini',
		'hora_inicio_fin',
		'hora_fin_fin',
		'estado_publicado'
	];

	public function area_nivels()
	{
		return $this->belongsToMany(AreaNivel::class, 'area_nivel_fase', 'id_fase', 'id_area_nivel')
			->withPivot('id', 'estado', 'fecha_ini', 'fecha_fin')
			->withTimestamps();
	}

	public function evaluacions()
	{
		return $this->hasMany(Evaluacion::class, 'id_fase');
	}

	public function actividads()
	{
		return $this->hasMany(Actividad::class, 'id_fase');
	}
}
