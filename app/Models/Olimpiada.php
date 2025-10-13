<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Olimpiada
 * 
 * @property int $id
 * @property string $nombre_olimpiada
 * @property int $gestion
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|NivelGrado[] $nivel_grados
 * @property Collection|ListaInscripcion[] $lista_inscripcions
 * @property Collection|AreaNivel[] $area_nivels
 * @property Collection|Fase[] $fases
 *
 * @package App\Models
 */
class Olimpiada extends Model
{
	protected $table = 'olimpiada';

	protected $casts = [
		'gestion' => 'int',
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime'
	];

	protected $fillable = [
		'nombre_olimpiada',
		'gestion',
		'fecha_inicio',
		'fecha_fin'
	];

	public function nivel_grados()
	{
		return $this->hasMany(NivelGrado::class, 'id_olimpiada');
	}

	public function lista_inscripcions()
	{
		return $this->hasMany(ListaInscripcion::class, 'id_olimpiada');
	}

	public function area_nivels()
	{
		return $this->hasMany(AreaNivel::class, 'id_olimpiada');
	}

	public function fases()
	{
		return $this->belongsToMany(Fase::class, 'olimpiada_fase', 'id_olimpiada', 'id_fase')
					->withPivot('id', 'estado', 'fecha_inicio', 'fecha_fin')
					->withTimestamps();
	}
}
