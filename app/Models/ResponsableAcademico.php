<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ResponsableAcademico
 * 
 * @property int $id
 * @property string $nombre
 * @property string $apellidos
 * @property string $correo
 * @property string $telefono
 * @property string $ci
 * @property string $area
 * @property Carbon $fecha_registro
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class ResponsableAcademico extends Model
{
	protected $table = 'responsable_academicos';

	protected $casts = [
		'fecha_registro' => 'datetime'
	];

	protected $fillable = [
		'nombre',
		'apellidos',
		'correo',
		'telefono',
		'ci',
		'area',
		'fecha_registro'
	];
}
