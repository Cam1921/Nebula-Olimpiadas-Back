<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Grado
 * 
 * @property int $id
 * @property string $nombre_grado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Competidor[] $competidors
 * @property Collection|Nivel[] $nivels
 *
 * @package App\Models
 */
class Grado extends Model
{
	protected $table = 'grado';

	protected $fillable = [
		'nombre_grado'
	];

	public function competidors()
	{
		return $this->hasMany(Competidor::class, 'id_grado');
	}

	public function nivels()
	{
		return $this->belongsToMany(Nivel::class, 'nivel_grado', 'id_grado', 'id_nivel')
					->withPivot('id', 'id_olimpiada')
					->withTimestamps();
	}
}
