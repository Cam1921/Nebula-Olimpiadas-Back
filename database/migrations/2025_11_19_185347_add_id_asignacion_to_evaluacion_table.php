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
            $table->foreignId('id_asignacion')->nullable()->constrained('asignacion')->onDelete('cascade')->after('id_fase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluacion', function (Blueprint $table) {
            $table->dropForeign(['id_asignacion']);
            $table->dropColumn('id_asignacion');
        });
    }
};
