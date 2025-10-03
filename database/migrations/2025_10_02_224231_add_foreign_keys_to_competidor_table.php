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
        Schema::table('competidor', function (Blueprint $table) {
            $table->foreign(['id_institucion'])->references(['id'])->on('institucion')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['id_tutor_legal'])->references(['id'])->on('tutor')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['id_grado'])->references(['id'])->on('grado')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competidor', function (Blueprint $table) {
            $table->dropForeign('competidor_id_institucion_foreign');
            $table->dropForeign('competidor_id_tutor_legal_foreign');
            $table->dropForeign('competidor_id_grado_foreign');
        });
    }
};
