<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('area_nivel_fase', function (Blueprint $table) {
            $table->id();
            $table->string('estado')->default('Pendiente');
            $table->date('fecha_ini')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->foreignId('id_area_nivel')->constrained('area_nivel')->onDelete('cascade');
            $table->foreignId('id_fase')->constrained('fase')->onDelete('cascade');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('area_nivel_fase');
    }
};
