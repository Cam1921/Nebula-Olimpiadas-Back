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
 * @property int $id_rol
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Rol $rol
 * @property Collection|Asignacion[] $asignacions
 * @property Collection|Evaluacion[] $evaluacions
 *
 * @package App\Models
 */
class Persona extends Model
{
	protected $table = 'persona';

	protected $casts = [
		'id_usuario' => 'int',
		'id_rol' => 'int'
	];

	protected $fillable = [
		'ci',
		'nombres',
		'apellidos',
		'telefono',
		'email',
		'id_usuario',
		'id_rol'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'id_usuario');
	}

	public function rol()
	{
		return $this->belongsTo(Rol::class, 'id_rol');
	}

	public function asignacions()
	{
		return $this->hasMany(Asignacion::class, 'id_persona');
	}

	public function evaluacions()
	{
		return $this->hasMany(Evaluacion::class, 'id_evaluador');
	}
}
