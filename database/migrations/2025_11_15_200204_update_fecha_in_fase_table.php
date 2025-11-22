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
        Schema::table('fase', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->change();
            $table->date('fecha_fin')->nullable()->change();
            $table->time('hora_inicio_ini')->nullable();
            $table->time('hora_fin_ini')->nullable();
            $table->time('hora_inicio_fin')->nullable();
            $table->time('hora_fin_fin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fase', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->change();
            $table->date('fecha_fin')->nullable()->change();
            $table->time('hora_inicio_ini')->nullable();
            $table->time('hora_fin_ini')->nullable();
            $table->time('hora_inicio_fin')->nullable();
            $table->time('hora_fin_fin')->nullable();
        });
    }
};
