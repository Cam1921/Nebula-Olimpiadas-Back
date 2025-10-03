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
        Schema::create('nivel_grado', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('id_grado');
            $table->bigInteger('id_nivel');
            $table->bigInteger('id_olimpiada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nivel_grado');
    }
};
