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
        Schema::create('olimpiada_fase', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('estado')->default('en proceso');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->foreignId('id_olimpiada')->constrained('olimpiada')->onDelete('cascade');
            $table->foreignId('id_fase')->constrained('fase')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olimpiada_fase');
    }
};
