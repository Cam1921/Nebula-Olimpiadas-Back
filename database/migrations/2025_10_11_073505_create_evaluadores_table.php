<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluadores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('correo')->unique();
            $table->string('telefono')->unique(); // ✅ único
            $table->string('ci')->unique();       // ✅ CI único, 6-10 dígitos
            $table->string('area');     // ✅ área única
            $table->string('nivel');              // ✅ Nuevo campo: Primaria o Secundaria
            $table->date('fecha_registro');       // ✅ solo fecha
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluadores');
    }
};