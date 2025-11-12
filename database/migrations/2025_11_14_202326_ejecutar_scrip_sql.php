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
        $path = database_path('sql/olimpiada_inicial.sql');

        if (!file_exists($path)) {
            throw new Exception("El archivo SQL no existe: $path");
        }

        $sql = file_get_contents($path);

        // Ejecutar el script completo
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Aquí defines cómo revertir el script si se hace migrate:rollback

        // Ejemplo: eliminar el trigger y la tabla de auditoría
        DB::unprepared('DROP TRIGGER IF EXISTS trg_competidor_auditoria ON competidor;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_log_competidor();');
        DB::unprepared('DROP TABLE IF EXISTS competidor_auditoria;');

        // También podrías eliminar la olimpiada si lo deseas:
        DB::table('olimpiada')
            ->where('nombre_olimpiada', 'Olimpiada Científica 2026')
            ->delete();
    }
};
