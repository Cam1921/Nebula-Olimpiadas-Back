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
 * @property string $nombre_completo
 * @property string $ci
 * @property string $grado
 * @property int $id_institucion
 * @property int $id_tutor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Institucion $institucion
 * @property TutorCompetidor $tutor_competidor
 * @property Collection|Inscripcion[] $inscripcions
 *
 * @package App\Models
 */
class Competidor extends Model
{
	protected $table = 'competidor';

	protected $casts = [
		'id_institucion' => 'int',
		'id_tutor' => 'int'
	];

	protected $fillable = [
		'nombre_completo',
		'ci',
		'grado',
		'id_institucion',
		'id_tutor'
	];

	public function institucion()
	{
		return $this->belongsTo(Institucion::class, 'id_institucion');
	}

	public function tutor_competidor()
	{
		return $this->belongsTo(TutorCompetidor::class, 'id_tutor');
	}

	public function inscripcions()
	{
		return $this->hasMany(Inscripcion::class, 'id_competidor');
	}
}
