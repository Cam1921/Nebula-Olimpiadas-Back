<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Equipo
 * 
 * @property int $id
 * @property string $nombre_equipo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Competidor[] $competidors
 *
 * @package App\Models
 */
class Equipo extends Model
{
	protected $table = 'equipo';

	protected $fillable = [
		'nombre_equipo'
	];

	public function competidors()
	{
		return $this->hasMany(Competidor::class, 'id_equipo');
	}
}
