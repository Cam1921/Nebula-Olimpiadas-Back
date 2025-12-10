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
        Schema::table('evaluacion_auditoria', function (Blueprint $table) {
            $table->string('accion')->nullable();
            $table->string('motivo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluacion_auditoria', function (Blueprint $table) {
            $table->dropColumn(['accion', 'motivo']);
        });
    }
};
