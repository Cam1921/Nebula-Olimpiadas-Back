<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('fase', function (Blueprint $table) {
            $table->string('estado_publicado')->default('sin_fechas');
            $table->string('descripcion')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fase', function (Blueprint $table) {
            $table->dropColumn('estado_publicado');
            $table->string('descripcion')->nullable(false)->change();
        });
    }
};
