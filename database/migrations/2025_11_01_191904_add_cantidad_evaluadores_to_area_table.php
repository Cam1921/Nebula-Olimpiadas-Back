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
        Schema::table('area', function (Blueprint $table) {
            $table->integer('cantidad_evaluadores')->default(0);
            $table->boolean('es_grupal')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('area', function (Blueprint $table) {
            $table->dropColumn('cantidad_evaluadores');
            $table->dropColumn('es_grupal');
        });
    }
};
