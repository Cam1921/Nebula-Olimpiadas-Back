<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('responsable_academicos', function (Blueprint $table) {
            $table->unique('area');
        });
    }

    public function down()
    {
        Schema::table('responsable_academicos', function (Blueprint $table) {
            $table->dropUnique(['area']);
        });
    }
};