<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir campo es_reserva a los pilotos
        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('es_reserva')->default(false)->after('is_active');
        });

        // Reemplazar is_captain por role en el pivot de pilotos del equipo
        Schema::table('fantasy_team_drivers', function (Blueprint $table) {
            $table->dropColumn('is_captain');
            // titular: puntúa normal | team_manager: x2 puntos | banquillo: no puntúa
            $table->enum('role', ['titular', 'team_manager', 'banquillo'])->default('titular')->after('driver_id');
        });
    }

    public function down(): void
    {
        Schema::table('fantasy_team_drivers', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->boolean('is_captain')->default(false);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('es_reserva');
        });
    }
};
