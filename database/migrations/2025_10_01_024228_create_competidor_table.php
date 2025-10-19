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
        Schema::create('competidor', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('ci');
            $table->bigInteger('id_grado');
            $table->bigInteger('id_institucion');
            $table->bigInteger('id_tutor_legal');
            $table->foreignId('id_equipo')
                ->nullable()              // Permite que sea NULL
                ->constrained('equipo')   // Crea la llave foránea
                ->onDelete('set null');   // Si se borra el equipo, se pone NULL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competidor');
    }
};
