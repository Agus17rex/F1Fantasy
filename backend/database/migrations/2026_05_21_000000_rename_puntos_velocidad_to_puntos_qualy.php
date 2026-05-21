<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultados_carrera', function (Blueprint $table) {
            $table->renameColumn('puntos_velocidad', 'puntos_qualy');
        });
    }

    public function down(): void
    {
        Schema::table('resultados_carrera', function (Blueprint $table) {
            $table->renameColumn('puntos_qualy', 'puntos_velocidad');
        });
    }
};
