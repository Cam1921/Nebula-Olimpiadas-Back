<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Rol
 * 
 * @property int $id
 * @property string $nombre
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Persona[] $personas
 *
 * @package App\Models
 */
class Rol extends Model
{
	protected $table = 'rol';

	protected $fillable = [
		'nombre'
	];

	public function personas()
	{
		return $this->hasMany(Persona::class, 'id_rol');
	}
}
