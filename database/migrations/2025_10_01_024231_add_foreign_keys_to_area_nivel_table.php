<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('area_nivel', function (Blueprint $table) {
            $table->foreign(['id_area'])->references(['id'])->on('area')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['id_nivel'])->references(['id'])->on('nivel')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('area_nivel', function (Blueprint $table) {
            $table->dropForeign('area_nivel_id_area_foreign');
            $table->dropForeign('area_nivel_id_nivel_foreign');
        });
    }
};
