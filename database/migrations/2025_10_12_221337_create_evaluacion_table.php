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
        Schema::create('evaluacion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('nota', 5, 2)->nullable();
            $table->string('descripcion')->nullable();
            $table->string('estado')->default('pendiente');
            $table->boolean('respeto')->default(false);
            $table->boolean('integridad')->default(false);
            $table->boolean('puntualidad')->default(false);
            $table->foreignId('id_inscripcion')->constrained('inscripcion')->onDelete('cascade');
            $table->foreignId('id_fase')->constrained('fase')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion');
    }
};
