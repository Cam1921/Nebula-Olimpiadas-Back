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
        Schema::table('evaluacion', function (Blueprint $table) {
            $table->string('observacion')->nullable();
            $table->string('estado_confirmacion')->default('pendiente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluacion', function (Blueprint $table) {
            $table->dropColumn('observacion');
            $table->dropColumn('estado_confirmacion');
        });
    }
};
