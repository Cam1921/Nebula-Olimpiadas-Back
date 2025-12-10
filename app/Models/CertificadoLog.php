<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CertificadoLog
 * 
 * @property int $id
 * @property int $id_inscripcion
 * @property int|null $id_fase
 * @property string $accion
 * @property string|null $archivo
 * @property string|null $ip
 * @property string|null $user_agent
 * @property int|null $realizado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class CertificadoLog extends Model
{
	protected $table = 'certificado_logs';

	protected $casts = [
		'id_inscripcion' => 'int',
		'id_fase' => 'int',
		'realizado_por' => 'int'
	];

	protected $fillable = [
		'id_inscripcion',
		'id_fase',
		'accion',
		'archivo',
		'ip',
		'user_agent',
		'realizado_por'
	];
}
