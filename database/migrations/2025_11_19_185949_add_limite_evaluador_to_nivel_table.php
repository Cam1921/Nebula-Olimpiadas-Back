<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('nivel', function (Blueprint $table) {
            $table->integer('limite_evaluador')->default(30);
            $table->integer('cantidad_evaluadores')->default(1);
        });
    }
    public function down(): void
    {
        Schema::table('nivel', function (Blueprint $table) {
            $table->dropColumn('limite_evaluador');
            $table->dropColumn('cantidad_evaluadores');
        });
    }
};
