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
        Schema::create('clasificacion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('puntaje', 5, 2)->nullable();
            $table->string('estado_clasificacion', 20)->nullable();
            $table->integer('posicion')->nullable();
            $table->foreignId('id_inscrito')->constrained('inscripcion')->onDelete('cascade');
            $table->timestamp('fecha_registro')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clasificacion');
    }
};
