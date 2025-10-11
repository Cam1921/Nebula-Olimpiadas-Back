<?php
// app/Models/ResponsableAcademico.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponsableAcademico extends Model
{
    use HasFactory;

    protected $table = 'responsable_academicos';

    protected $fillable = [
        'nombre',
        'apellidos',
        'correo',
        'telefono',
        'ci',
        'area',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
    ];
}