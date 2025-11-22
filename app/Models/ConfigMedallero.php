<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ConfigMedallero
 * 
 * @property int $id
 * @property int $oros
 * @property int $platas
 * @property int $bronces
 * @property int $menciones_honorificas
 * @property int $id_area_nivel
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property AreaNivel $area_nivel
 *
 * @package App\Models
 */
class ConfigMedallero extends Model
{
	protected $table = 'config_medallero';

	protected $casts = [
		'oros' => 'int',
		'platas' => 'int',
		'bronces' => 'int',
		'menciones_honorificas' => 'int',
		'id_area_nivel' => 'int'
	];

	protected $fillable = [
		'oros',
		'platas',
		'bronces',
		'menciones_honorificas',
		'id_area_nivel'
	];

	public function area_nivel()
	{
		return $this->belongsTo(AreaNivel::class, 'id_area_nivel');
	}
}
