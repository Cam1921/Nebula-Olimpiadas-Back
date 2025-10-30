<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AreaNivelFase
 * 
 * @property int $id
 * @property string $estado
 * @property Carbon|null $fecha_ini
 * @property Carbon|null $fecha_fin
 * @property int $id_area_nivel
 * @property int $id_fase
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property AreaNivel $area_nivel
 * @property Fase $fase
 *
 * @package App\Models
 */
class AreaNivelFase extends Model
{
	protected $table = 'area_nivel_fase';

	protected $casts = [
		'fecha_ini' => 'datetime',
		'fecha_fin' => 'datetime',
		'id_area_nivel' => 'int',
		'id_fase' => 'int'
	];

	protected $fillable = [
		'estado',
		'fecha_ini',
		'fecha_fin',
		'id_area_nivel',
		'id_fase'
	];

	public function area_nivel()
	{
		return $this->belongsTo(AreaNivel::class, 'id_area_nivel');
	}

	public function fase()
	{
		return $this->belongsTo(Fase::class, 'id_fase');
	}
}
