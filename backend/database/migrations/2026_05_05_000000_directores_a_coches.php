<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reemplaza el concepto de "Director de equipo" por "Coche".
 *
 * - directores_equipo            → coches_equipo
 * - equipos_fantasy_directores   → equipos_fantasy_coches
 * - columna director_id          → coche_id
 * - resultados_carrera: añade puntos_carrera y puntos_velocidad
 *   para soportar el nuevo sistema de puntuación diferenciada.
 *
 * Idempotente: comprueba existencia antes de actuar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── Renombrar tablas ────────────────────────────────────────────────
        if (Schema::hasTable('directores_equipo') && !Schema::hasTable('coches_equipo')) {
            Schema::rename('directores_equipo', 'coches_equipo');
        }

        if (Schema::hasTable('equipos_fantasy_directores') && !Schema::hasTable('equipos_fantasy_coches')) {
            Schema::rename('equipos_fantasy_directores', 'equipos_fantasy_coches');
        }

        // ─── Renombrar columna director_id → coche_id ────────────────────────
        if (Schema::hasTable('equipos_fantasy_coches')
            && Schema::hasColumn('equipos_fantasy_coches', 'director_id')
            && !Schema::hasColumn('equipos_fantasy_coches', 'coche_id')) {
            Schema::table('equipos_fantasy_coches', function (Blueprint $t) {
                $t->renameColumn('director_id', 'coche_id');
            });
        }

        // ─── resultados_carrera: añadir puntos_carrera y puntos_velocidad ────
        // puntos_carrera    → posición final + penalizaciones (DNF, DNS, descalif)
        // puntos_velocidad  → posición clasificación + vuelta rápida + piloto del día
        Schema::table('resultados_carrera', function (Blueprint $t) {
            if (!Schema::hasColumn('resultados_carrera', 'puntos_carrera')) {
                $t->integer('puntos_carrera')->default(0)->after('puntos_fantasy');
            }
            if (!Schema::hasColumn('resultados_carrera', 'puntos_velocidad')) {
                $t->integer('puntos_velocidad')->default(0)->after('puntos_carrera');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resultados_carrera', function (Blueprint $t) {
            if (Schema::hasColumn('resultados_carrera', 'puntos_velocidad')) {
                $t->dropColumn('puntos_velocidad');
            }
            if (Schema::hasColumn('resultados_carrera', 'puntos_carrera')) {
                $t->dropColumn('puntos_carrera');
            }
        });

        if (Schema::hasTable('equipos_fantasy_coches')
            && Schema::hasColumn('equipos_fantasy_coches', 'coche_id')) {
            Schema::table('equipos_fantasy_coches', function (Blueprint $t) {
                $t->renameColumn('coche_id', 'director_id');
            });
        }

        if (Schema::hasTable('equipos_fantasy_coches')) {
            Schema::rename('equipos_fantasy_coches', 'equipos_fantasy_directores');
        }
        if (Schema::hasTable('coches_equipo')) {
            Schema::rename('coches_equipo', 'directores_equipo');
        }
    }
};
