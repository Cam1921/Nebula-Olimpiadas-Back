<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponsableAcademico extends Model
{
    use HasFactory;

    protected $table = 'responsable_academicos'; // ← plural coherente

    protected $fillable = [
        'nombre',
        'apellidos',
        'correo',
        'telefono',
        'area',
    ];
}