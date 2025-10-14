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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Grado[] $grados
 * @property Collection|Area[] $areas
 *
 * @package App\Models
 */
class Nivel extends Model
{
	protected $table = 'nivel';

	protected $fillable = [
		'nombre_nivel'
	];

	public function grados()
	{
		return $this->belongsToMany(Grado::class, 'nivel_grado', 'id_nivel', 'id_grado')
					->withPivot('id', 'id_olimpiada')
					->withTimestamps();
	}

	public function areas()
	{
		return $this->belongsToMany(Area::class, 'area_nivel', 'id_nivel', 'id_area')
					->withPivot('id', 'id_olimpiada')
					->withTimestamps();
	}
}
