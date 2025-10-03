<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class NivelGrado
 * 
 * @property int $id
 * @property int $id_grado
 * @property int $id_nivel
 * @property int $id_olimpiada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Grado $grado
 * @property Nivel $nivel
 * @property Olimpiada $olimpiada
 *
 * @package App\Models
 */
class NivelGrado extends Model
{
	protected $table = 'nivel_grado';

	protected $casts = [
		'id_grado' => 'int',
		'id_nivel' => 'int',
		'id_olimpiada' => 'int'
	];

	protected $fillable = [
		'id_grado',
		'id_nivel',
		'id_olimpiada'
	];

	public function grado()
	{
		return $this->belongsTo(Grado::class, 'id_grado');
	}

	public function nivel()
	{
		return $this->belongsTo(Nivel::class, 'id_nivel');
	}

	public function olimpiada()
	{
		return $this->belongsTo(Olimpiada::class, 'id_olimpiada');
	}
}
