<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Asignacion
 * 
 * @property int $id
 * @property int $id_persona
 * @property int $id_area
 * @property int|null $id_nivel
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Persona $persona
 * @property Area $area
 * @property Nivel|null $nivel
 *
 * @package App\Models
 */
class Asignacion extends Model
{
	protected $table = 'asignacion';

	protected $casts = [
		'id_persona' => 'int',
		'id_area' => 'int',
		'id_nivel' => 'int'
	];

	protected $fillable = [
		'id_persona',
		'id_area',
		'id_nivel'
	];

	public function persona()
	{
		return $this->belongsTo(Persona::class, 'id_persona');
	}

	public function area()
	{
		return $this->belongsTo(Area::class, 'id_area');
	}

	public function nivel()
	{
		return $this->belongsTo(Nivel::class, 'id_nivel');
	}
}
