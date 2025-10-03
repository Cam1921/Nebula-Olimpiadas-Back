<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ListaInscripcion
 * 
 * @property int $id
 * @property int $id_olimpiada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Olimpiada $olimpiada
 * @property Collection|Inscripcion[] $inscripcions
 *
 * @package App\Models
 */
class ListaInscripcion extends Model
{
	protected $table = 'lista_inscripcion';

	protected $casts = [
		'id_olimpiada' => 'int'
	];

	protected $fillable = [
		'id_olimpiada'
	];

	public function olimpiada()
	{
		return $this->belongsTo(Olimpiada::class, 'id_olimpiada');
	}

	public function inscripcions()
	{
		return $this->hasMany(Inscripcion::class, 'id_lista_inscripcion');
	}
}
