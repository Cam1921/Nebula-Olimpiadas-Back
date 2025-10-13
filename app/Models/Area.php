<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Area
 * 
 * @property int $id
 * @property string $nombre_area
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Nivel[] $nivels
 * @property Collection|Asignacion[] $asignacions
 *
 * @package App\Models
 */
class Area extends Model
{
	protected $table = 'area';

	protected $fillable = [
		'nombre_area'
	];

	public function nivels()
	{
		return $this->belongsToMany(Nivel::class, 'area_nivel', 'id_area', 'id_nivel')
					->withPivot('id', 'id_olimpiada')
					->withTimestamps();
	}

	public function asignacions()
	{
		return $this->hasMany(Asignacion::class, 'id_area');
	}
}
