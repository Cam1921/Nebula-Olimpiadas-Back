<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OlimpiadaFase
 * 
 * @property int $id
 * @property string $estado
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property int $id_olimpiada
 * @property int $id_fase
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Olimpiada $olimpiada
 * @property Fase $fase
 * @property Collection|Evaluacion[] $evaluacions
 *
 * @package App\Models
 */
class OlimpiadaFase extends Model
{
	protected $table = 'olimpiada_fase';

	protected $casts = [
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime',
		'id_olimpiada' => 'int',
		'id_fase' => 'int'
	];

	protected $fillable = [
		'estado',
		'fecha_inicio',
		'fecha_fin',
		'id_olimpiada',
		'id_fase'
	];

	public function olimpiada()
	{
		return $this->belongsTo(Olimpiada::class, 'id_olimpiada');
	}

	public function fase()
	{
		return $this->belongsTo(Fase::class, 'id_fase');
	}

	public function evaluacions()
	{
		return $this->hasMany(Evaluacion::class, 'id_ompiada_fase');
	}
}
