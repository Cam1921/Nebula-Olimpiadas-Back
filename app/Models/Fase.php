<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Fase
 * 
 * @property int $id
 * @property string $nombre
 * @property string $descripcion
 * @property string $estado
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Evaluacion[] $evaluacions
 *
 * @package App\Models
 */
class Fase extends Model
{
	protected $table = 'fase';

	protected $casts = [
		'fecha_inicio' => 'datetime',
		'fecha_fin' => 'datetime'
	];

	protected $fillable = [
		'nombre',
		'descripcion',
		'estado',
		'fecha_inicio',
		'fecha_fin'
	];

	public function evaluacions()
	{
		return $this->hasMany(Evaluacion::class, 'id_fase');
	}
}
