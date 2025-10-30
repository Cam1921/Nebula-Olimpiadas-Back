<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Invitacion
 * 
 * @property int $id
 * @property string $nombres
 * @property string $apellidos
 * @property string $email
 * @property string $rol
 * @property string $token
 * @property Carbon|null $token_expira_en
 * @property bool $usado
 * @property string $estado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Invitacion extends Model
{
	protected $table = 'invitacion';

	protected $casts = [
		'token_expira_en' => 'datetime',
		'usado' => 'bool'
	];

	protected $hidden = [
		'token'
	];

	protected $fillable = [
		'nombres',
		'apellidos',
		'email',
		'rol',
		'token',
		'token_expira_en',
		'usado',
		'estado'
	];
}
