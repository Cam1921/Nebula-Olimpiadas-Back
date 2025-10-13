<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ImportTemp
 * 
 * @property uuid $import_id
 * @property int $fila
 * @property string|null $datos
 * @property string|null $errores
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class ImportTemp extends Model
{
	protected $table = 'import_temp';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'import_id' => 'uuid',
		'fila' => 'int',
		'datos' => 'binary',
		'errores' => 'binary'
	];

	protected $fillable = [
		'datos',
		'errores'
	];
}
