<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Nivel
 * 
 * @property int $id
 * @property string $nombre_nivel
 * @property string $descripcion_nivel
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Area[] $areas
 *
 * @package App\Models
 */
class Nivel extends Model
{
	protected $table = 'nivel';

	protected $fillable = [
		'nombre_nivel',
		'descripcion_nivel'
	];

	public function areas()
	{
		return $this->belongsToMany(Area::class, 'area_nivel', 'id_nivel', 'id_area')
					->withPivot('id')
					->withTimestamps();
	}
}
