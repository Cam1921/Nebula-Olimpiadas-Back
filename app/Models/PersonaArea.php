<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PersonaArea
 * 
 * @property int $id
 * @property int $id_persona
 * @property int $id_area
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Persona $persona
 * @property Area $area
 *
 * @package App\Models
 */
class PersonaArea extends Model
{
	protected $table = 'persona_area';

	protected $casts = [
		'id_persona' => 'int',
		'id_area' => 'int'
	];

	protected $fillable = [
		'id_persona',
		'id_area'
	];

	public function persona()
	{
		return $this->belongsTo(Persona::class, 'id_persona');
	}

	public function area()
	{
		return $this->belongsTo(Area::class, 'id_area');
	}
}
