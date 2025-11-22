<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('actividad', function (Blueprint $table) {
            $table->string('estado_publicado')->default('sin_fechas');
            $table->foreignId('id_fase')->nullable()->change();
            $table->dropUnique('actividad_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('actividad', function (Blueprint $table) {
            $table->dropColumn('estado_publicado');
            $table->foreignId('id_fase')->nullable()->change();
            $table->unique('nombre');
        });
    }
};
