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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Area $area
 * @property Nivel $nivel
 * @property Collection|Inscripcion[] $inscripcions
 *
 * @package App\Models
 */
class AreaNivel extends Model
{
	protected $table = 'area_nivel';

	protected $casts = [
		'id_area' => 'int',
		'id_nivel' => 'int'
	];

	protected $fillable = [
		'id_area',
		'id_nivel'
	];

	public function area()
	{
		return $this->belongsTo(Area::class, 'id_area');
	}

	public function nivel()
	{
		return $this->belongsTo(Nivel::class, 'id_nivel');
	}

	public function inscripcions()
	{
		return $this->hasMany(Inscripcion::class, 'id_area_nivel');
	}
}
