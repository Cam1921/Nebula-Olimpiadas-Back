<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluador extends Model
{
    use HasFactory;

    protected $table = 'evaluadores'; // Nombre de la tabla

    protected $fillable = [
        'nombre',
        'apellidos',
        'correo',
        'telefono',
        'ci',
        'area',
        'nivel', // Nuevo campo
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
    ];
}