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
        Schema::create('competidor', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre_completo');
            $table->string('ci');
            $table->string('grado');
            $table->bigInteger('id_institucion');
            $table->bigInteger('id_tutor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competidor');
    }
};
