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
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->foreign(['id_area_nivel'])->references(['id'])->on('area_nivel')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['id_competidor'])->references(['id'])->on('competidor')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['id_tutor_academico'])->references(['id'])->on('tutor')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['id_lista_inscripcion'])->references(['id'])->on('lista_inscripcion')->onUpdate('no action')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripcion', function (Blueprint $table) {
            $table->dropForeign('inscripcion_id_area_nivel_foreign');
            $table->dropForeign('inscripcion_id_competidor_foreign');
            $table->dropForeign('inscripcion_id_tutor_academico_foreign');
            $table->dropForeign('inscripcion_id_lista_inscripcion_foreign');
        });
    }
};
