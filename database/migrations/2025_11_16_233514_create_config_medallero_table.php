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
        Schema::create('config_medallero', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger("oros");
            $table->unsignedInteger("platas");
            $table->unsignedInteger("bronces");
            $table->unsignedInteger("menciones_honorificas");

            $table->foreignId('id_area_nivel')
                ->constrained('area_nivel')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_medallero');
    }
};
