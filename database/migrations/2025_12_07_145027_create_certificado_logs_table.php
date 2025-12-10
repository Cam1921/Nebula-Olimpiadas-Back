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
        Schema::create('certificado_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_inscripcion'); // referencia, no FK
            $table->unsignedBigInteger('id_fase')->nullable(); // referencia, no FK
            $table->string('accion'); // generado, descargado, reimpreso
            $table->string('archivo')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedBigInteger('realizado_por')->nullable(); // referencia, no FK
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificado_logs');
    }
};
