<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TutorCompetidor
 * 
 * @property int $id
 * @property string $nombre_completo
 * @property string $telefono
 * @property string $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Competidor[] $competidors
 *
 * @package App\Models
 */
class TutorCompetidor extends Model
{
	protected $table = 'tutor_competidor';

	protected $fillable = [
		'nombre_completo',
		'telefono',
		'email'
	];

	public function competidors()
	{
		return $this->hasMany(Competidor::class, 'id_tutor');
	}
}
