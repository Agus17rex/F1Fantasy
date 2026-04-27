<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de directores/team principals
        Schema::create('team_principals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nationality')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('constructor_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('price')->default(10_000_000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pivot: qué director tiene cada equipo fantasy
        Schema::create('fantasy_team_principals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fantasy_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_principal_id')->constrained()->cascadeOnDelete();
            $table->timestamp('selected_at');
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
        });

        // Quitar el role team_manager del enum (ahora solo titular / banquillo)
        DB::statement("ALTER TABLE fantasy_team_drivers MODIFY role ENUM('titular','banquillo') DEFAULT 'titular'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE fantasy_team_drivers MODIFY role ENUM('titular','team_manager','banquillo') DEFAULT 'titular'");
        Schema::dropIfExists('fantasy_team_principals');
        Schema::dropIfExists('team_principals');
    }
};
