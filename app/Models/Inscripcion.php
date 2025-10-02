<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Inscripcion
 * 
 * @property int $id
 * @property int $id_competidor
 * @property int $id_area_nivel
 * @property string $gestion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Competidor $competidor
 * @property AreaNivel $area_nivel
 *
 * @package App\Models
 */
class Inscripcion extends Model
{
	protected $table = 'inscripcion';

	protected $casts = [
		'id_competidor' => 'int',
		'id_area_nivel' => 'int'
	];

	protected $fillable = [
		'id_competidor',
		'id_area_nivel',
		'gestion'
	];

	public function competidor()
	{
		return $this->belongsTo(Competidor::class, 'id_competidor');
	}

	public function area_nivel()
	{
		return $this->belongsTo(AreaNivel::class, 'id_area_nivel');
	}
}
