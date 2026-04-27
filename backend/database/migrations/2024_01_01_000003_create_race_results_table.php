<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resultados de carrera (oficial)
        Schema::create('race_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('constructor_id')->constrained()->cascadeOnDelete();
            $table->integer('grid_position')->nullable();       // Posición salida
            $table->integer('finish_position')->nullable();     // Posición final
            $table->string('status')->default('Finished');      // 'Finished', '+1 Lap', 'DNF', etc.
            $table->integer('points_official')->default(0);     // Puntos oficiales F1
            $table->boolean('fastest_lap')->default(false);
            $table->boolean('driver_of_the_day')->default(false);
            $table->integer('qualifying_position')->nullable();
            // Puntos fantasy calculados
            $table->integer('fantasy_points')->default(0);
            $table->boolean('fantasy_points_calculated')->default(false);
            $table->timestamps();

            $table->unique(['race_id', 'driver_id']);
        });

        // Sistema de puntuación fantasy
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event');            // 'P1_FINISH', 'FASTEST_LAP', 'DNF', etc.
            $table->integer('points');          // Puntos para ese evento
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_rules');
        Schema::dropIfExists('race_results');
    }
};
