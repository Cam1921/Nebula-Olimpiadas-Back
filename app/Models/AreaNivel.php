<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AreaNivel
 * 
 * @property int $id
 * @property int $id_area
 * @property int $id_nivel
 * @property int $id_olimpiada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Area $area
 * @property Nivel $nivel
 * @property Olimpiada $olimpiada
 * @property Collection|Inscripcion[] $inscripcions
 * @property Collection|Asignacion[] $asignacions
 * @property Collection|Fase[] $fases
 * @property Collection|ConfigMedallero[] $config_medalleros
 *
 * @package App\Models
 */
class AreaNivel extends Model
{
	protected $table = 'area_nivel';

	protected $casts = [
		'id_area' => 'int',
		'id_nivel' => 'int',
		'id_olimpiada' => 'int'
	];

	protected $fillable = [
		'id_area',
		'id_nivel',
		'id_olimpiada'
	];

	public function area()
	{
		return $this->belongsTo(Area::class, 'id_area');
	}

	public function nivel()
	{
		return $this->belongsTo(Nivel::class, 'id_nivel');
	}

	public function olimpiada()
	{
		return $this->belongsTo(Olimpiada::class, 'id_olimpiada');
	}

	public function inscripcions()
	{
		return $this->hasMany(Inscripcion::class, 'id_area_nivel');
	}

	public function asignacions()
	{
		return $this->hasMany(Asignacion::class, 'id_area_nivel');
	}

	public function fases()
	{
		return $this->belongsToMany(Fase::class, 'area_nivel_fase', 'id_area_nivel', 'id_fase')
			->withPivot('id', 'estado', 'fecha_ini', 'fecha_fin')
			->withTimestamps();
	}

	public function config_medalleros()
	{
		return $this->hasMany(ConfigMedallero::class, 'id_area_nivel');
	}
	public function area_nivel_fase()
	{
		return $this->hasMany(AreaNivelFase::class, 'id_area_nivel');
	}
}
