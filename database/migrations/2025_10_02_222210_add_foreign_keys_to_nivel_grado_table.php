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
        Schema::table('nivel_grado', function (Blueprint $table) {
            $table->foreign(['id_grado'])->references(['id'])->on('grado')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['id_nivel'])->references(['id'])->on('nivel')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['id_olimpiada'])->references(['id'])->on('olimpiada')->onUpdate('no action')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nivel_grado', function (Blueprint $table) {
            $table->dropForeign('nivel_grado_id_grado_foreign');
            $table->dropForeign('nivel_grado_id_nivel_foreign');
            $table->dropForeign('nivel_grado_id_olimpiada_foreign');

        });
    }
};
