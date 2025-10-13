<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CompetidorAuditorium
 * 
 * @property int $id
 * @property int|null $competidor_id
 * @property string|null $accion
 * @property string|null $datos
 * @property Carbon|null $created_at
 *
 * @package App\Models
 */
class CompetidorAuditorium extends Model
{
	protected $table = 'competidor_auditoria';
	public $timestamps = false;

	protected $casts = [
		'competidor_id' => 'int',
		'datos' => 'binary'
	];

	protected $fillable = [
		'competidor_id',
		'accion',
		'datos'
	];
}
