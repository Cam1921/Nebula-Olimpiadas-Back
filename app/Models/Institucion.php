<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Institucion
 * 
 * @property int $id
 * @property string $nombre_institucion
 * @property string $departamento_institucion
 * @property string $municipio_institucion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Competidor[] $competidors
 *
 * @package App\Models
 */
class Institucion extends Model
{
	protected $table = 'institucion';

	protected $fillable = [
		'nombre_institucion',
		'departamento_institucion',
		'municipio_institucion'
	];

	public function competidors()
	{
		return $this->hasMany(Competidor::class, 'id_institucion');
	}
}
