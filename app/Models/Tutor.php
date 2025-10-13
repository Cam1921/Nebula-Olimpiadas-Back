<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Tutor
 * 
 * @property int $id
 * @property string $ci
 * @property string $nombres
 * @property string $apellidos
 * @property string $telefono
 * @property string $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Inscripcion[] $inscripcions
 * @property Collection|Competidor[] $competidors
 *
 * @package App\Models
 */
class Tutor extends Model
{
	protected $table = 'tutor';

	protected $fillable = [
		'ci',
		'nombres',
		'apellidos',
		'telefono',
		'email'
	];

	public function inscripcions()
	{
		return $this->hasMany(Inscripcion::class, 'id_tutor_academico');
	}

	public function competidors()
	{
		return $this->hasMany(Competidor::class, 'id_tutor_legal');
	}
}
