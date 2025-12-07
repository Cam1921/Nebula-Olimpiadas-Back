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
        Schema::create('ranking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_fase')->constrained('fase')->onDelete('cascade');
            $table->foreignId('id_inscripcion')->constrained('inscripcion')->onDelete('cascade');
            $table->integer('puesto_oficial')->nullable();
            $table->string('estado_final')->nullable();
            $table->decimal('puntaje_total', 8, 2)->nullable();
            $table->string('observacion')->nullable();
            $table->timestamp('publicado_en')->nullable();
            $table->index(['id_fase', 'id_inscripcion']);
            $table->index('estado_final');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranking');
    }
};
