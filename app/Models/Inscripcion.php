<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Inscripcion
 * 
 * @property int $id
 * @property int $id_competidor
 * @property int $id_area_nivel
 * @property int $id_lista_inscripcion
 * @property int|null $id_tutor_academico
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Competidor $competidor
 * @property AreaNivel $area_nivel
 * @property ListaInscripcion $lista_inscripcion
 * @property Tutor|null $tutor
 * @property Collection|Clasificacion[] $clasificacions
 * @property Collection|Evaluacion[] $evaluacions
 *
 * @package App\Models
 */
class Inscripcion extends Model
{
	protected $table = 'inscripcion';

	protected $casts = [
		'id_competidor' => 'int',
		'id_area_nivel' => 'int',
		'id_lista_inscripcion' => 'int',
		'id_tutor_academico' => 'int'
	];

	protected $fillable = [
		'id_competidor',
		'id_area_nivel',
		'id_lista_inscripcion',
		'id_tutor_academico'
	];

	public function competidor()
	{
		return $this->belongsTo(Competidor::class, 'id_competidor');
	}

	public function area_nivel()
	{
		return $this->belongsTo(AreaNivel::class, 'id_area_nivel');
	}

	public function lista_inscripcion()
	{
		return $this->belongsTo(ListaInscripcion::class, 'id_lista_inscripcion');
	}

	public function tutor()
	{
		return $this->belongsTo(Tutor::class, 'id_tutor_academico');
	}

	public function clasificacions()
	{
		return $this->hasMany(Clasificacion::class, 'id_inscrito');
	}

	public function evaluacions()
	{
		return $this->hasMany(Evaluacion::class, 'id_inscripcion');
	}
}
