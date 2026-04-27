<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ligas fantasy
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 8)->unique();    // Código de invitación
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->integer('max_members')->default(20);
            $table->boolean('is_private')->default(true);
            $table->integer('season');
            $table->enum('status', ['active', 'finished', 'draft'])->default('draft');
            $table->timestamps();
        });

        // Miembros de ligas
        Schema::create('league_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('total_points')->default(0);
            $table->integer('rank')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['league_id', 'user_id']);
        });

        // Equipos fantasy (1 por usuario por liga)
        Schema::create('fantasy_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('remaining_budget')->default(100000000); // 100M
            $table->integer('total_points')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'league_id']);
        });

        // Selección de pilotos en el equipo fantasy
        Schema::create('fantasy_team_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fantasy_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_captain')->default(false);      // Capitán: puntos x2
            $table->timestamp('selected_at');
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
        });

        // Selección de escudería en el equipo fantasy
        Schema::create('fantasy_team_constructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fantasy_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('constructor_id')->constrained()->cascadeOnDelete();
            $table->timestamp('selected_at');
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
        });

        // Puntos ganados por carrera (historial)
        Schema::create('fantasy_team_race_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fantasy_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->integer('points_earned')->default(0);
            $table->json('breakdown')->nullable();  // Desglose por piloto/escudería
            $table->timestamps();

            $table->unique(['fantasy_team_id', 'race_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fantasy_team_race_points');
        Schema::dropIfExists('fantasy_team_constructors');
        Schema::dropIfExists('fantasy_team_drivers');
        Schema::dropIfExists('fantasy_teams');
        Schema::dropIfExists('league_members');
        Schema::dropIfExists('leagues');
    }
};
