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
        Schema::create('import_temp', function (Blueprint $table) {
            $table->uuid('import_id');
            $table->integer('fila');
            $table->jsonb('datos')->nullable();
            $table->jsonb('errores')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['import_id', 'fila']); // clave primaria compuesta
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_temp');
    }
};
