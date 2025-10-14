<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Persona
 * 
 * @property int $id
 * @property string $ci
 * @property string $nombres
 * @property string $apellidos
 * @property string $telefono
 * @property string $email
 * @property int $id_usuario
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Collection|Asignacion[] $asignacions
 * @property Collection|Rol[] $rols
 *
 * @package App\Models
 */
class Persona extends Model
{
	protected $table = 'persona';

	protected $casts = [
		'id_usuario' => 'int'
	];

	protected $fillable = [
		'ci',
		'nombres',
		'apellidos',
		'telefono',
		'email',
		'id_usuario'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'id_usuario');
	}

	public function asignacions()
	{
		return $this->hasMany(Asignacion::class, 'id_persona');
	}

	public function rols()
	{
		return $this->belongsToMany(Rol::class, 'persona_rol', 'id_persona', 'id_rol')
					->withPivot('id')
					->withTimestamps();
	}
}
