<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('actividad', function (Blueprint $table) {
            $table->id();
            $table->string("nombre")->unique();
            $table->text("descripcion")->nullable();
            // Fecha inicio + rango horario
            $table->date("fecha_inicio")->nullable();
            $table->time("hora_inicio_ini")->nullable();
            $table->time("hora_fin_ini")->nullable();
            // Fecha fin + rango horario
            $table->date("fecha_fin")->nullable();
            $table->time("hora_inicio_fin")->nullable();
            $table->time("hora_fin_fin")->nullable();
            $table->foreignId("id_fase")->constrained("fase")->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividad');
    }
};
