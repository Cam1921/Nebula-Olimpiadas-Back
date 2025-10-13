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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Olimpiada[] $olimpiadas
 *
 * @package App\Models
 */
class Fase extends Model
{
	protected $table = 'fase';

	protected $fillable = [
		'nombre',
		'descripcion'
	];

	public function olimpiadas()
	{
		return $this->belongsToMany(Olimpiada::class, 'olimpiada_fase', 'id_fase', 'id_olimpiada')
					->withPivot('id', 'estado', 'fecha_inicio', 'fecha_fin')
					->withTimestamps();
	}
}
