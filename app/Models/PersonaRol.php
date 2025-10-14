<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PersonaRol
 * 
 * @property int $id
 * @property int $id_persona
 * @property int $id_rol
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Persona $persona
 * @property Rol $rol
 *
 * @package App\Models
 */
class PersonaRol extends Model
{
	protected $table = 'persona_rol';

	protected $casts = [
		'id_persona' => 'int',
		'id_rol' => 'int'
	];

	protected $fillable = [
		'id_persona',
		'id_rol'
	];

	public function persona()
	{
		return $this->belongsTo(Persona::class, 'id_persona');
	}

	public function rol()
	{
		return $this->belongsTo(Rol::class, 'id_rol');
	}
}
