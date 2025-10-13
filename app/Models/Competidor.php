<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Competidor
 * 
 * @property int $id
 * @property string $nombres
 * @property string $apellidos
 * @property string $ci
 * @property int $id_grado
 * @property int $id_institucion
 * @property int $id_tutor_legal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Grado $grado
 * @property Institucion $institucion
 * @property Tutor $tutor
 * @property Collection|Inscripcion[] $inscripcions
 *
 * @package App\Models
 */
class Competidor extends Model
{
	protected $table = 'competidor';

	protected $casts = [
		'id_grado' => 'int',
		'id_institucion' => 'int',
		'id_tutor_legal' => 'int'
	];

	protected $fillable = [
		'nombres',
		'apellidos',
		'ci',
		'id_grado',
		'id_institucion',
		'id_tutor_legal'
	];

	public function grado()
	{
		return $this->belongsTo(Grado::class, 'id_grado');
	}

	public function institucion()
	{
		return $this->belongsTo(Institucion::class, 'id_institucion');
	}

	public function tutor()
	{
		return $this->belongsTo(Tutor::class, 'id_tutor_legal');
	}

	public function inscripcions()
	{
		return $this->hasMany(Inscripcion::class, 'id_competidor');
	}
}
