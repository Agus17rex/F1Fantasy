<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Traducción del esquema completo a español.
 *
 * Idempotente: cada paso comprueba si ya se hizo antes de actuar,
 * de modo que se puede reintentar sin problemas si la primera ejecución
 * falla a mitad.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── USERS (mantenemos la tabla) ────────────────────────────────────
        $this->renameColumns('users', [
            'name'     => 'nombre',
            'username' => 'usuario',
        ]);
        if (Schema::hasColumn('users', 'role')) {
            DB::statement("ALTER TABLE users CHANGE `role` `rol` ENUM('user','admin') NOT NULL DEFAULT 'user'");
        }

        // ─── DRIVERS → PILOTOS ───────────────────────────────────────────────
        $this->renameColumns('drivers', [
            'first_name'     => 'nombre',
            'last_name'      => 'apellido',
            'nationality'    => 'nacionalidad',
            'code'           => 'codigo',
            'number'         => 'numero',
            'date_of_birth'  => 'fecha_nacimiento',
            'photo'          => 'foto',
            'constructor_id' => 'escuderia_id',
            'price'          => 'precio',
            'is_active'      => 'activo',
        ]);
        $this->renameTable('drivers', 'pilotos');

        // ─── CONSTRUCTORS → ESCUDERIAS ───────────────────────────────────────
        $this->renameColumns('constructors', [
            'name'        => 'nombre',
            'nationality' => 'nacionalidad',
            'price'       => 'precio',
            'is_active'   => 'activa',
        ]);
        $this->renameTable('constructors', 'escuderias');

        // ─── TEAM_PRINCIPALS → DIRECTORES_EQUIPO ────────────────────────────
        $this->renameColumns('team_principals', [
            'name'           => 'nombre',
            'nationality'    => 'nacionalidad',
            'photo'          => 'foto',
            'constructor_id' => 'escuderia_id',
            'price'          => 'precio',
            'is_active'      => 'activo',
        ]);
        $this->renameTable('team_principals', 'directores_equipo');

        // ─── CIRCUITS → CIRCUITOS ────────────────────────────────────────────
        $this->renameColumns('circuits', [
            'name'     => 'nombre',
            'country'  => 'pais',
            'location' => 'ubicacion',
            'image'    => 'imagen',
        ]);
        $this->renameTable('circuits', 'circuitos');

        // ─── RACES → CARRERAS ───────────────────────────────────────────────
        $this->renameColumns('races', [
            'name'              => 'nombre',
            'season'            => 'temporada',
            'round'             => 'ronda',
            'circuit_id'        => 'circuito_id',
            'date'              => 'fecha',
            'time'              => 'hora',
            'is_sprint'         => 'es_sprint',
            'transfer_deadline' => 'cierre_mercado',
        ]);
        if (Schema::hasTable('races') && Schema::hasColumn('races', 'status')) {
            DB::statement("ALTER TABLE races CHANGE `status` `estado` ENUM('upcoming','active','scored','cancelled') NOT NULL DEFAULT 'upcoming'");
        }
        $this->renameTable('races', 'carreras');

        // ─── RACE_RESULTS → RESULTADOS_CARRERA ──────────────────────────────
        $this->renameColumns('race_results', [
            'race_id'                   => 'carrera_id',
            'driver_id'                 => 'piloto_id',
            'constructor_id'            => 'escuderia_id',
            'grid_position'             => 'posicion_salida',
            'finish_position'           => 'posicion_final',
            'qualifying_position'       => 'posicion_clasificacion',
            'points_official'           => 'puntos_oficiales',
            'fastest_lap'               => 'vuelta_rapida',
            'driver_of_the_day'         => 'piloto_del_dia',
            'fantasy_points'            => 'puntos_fantasy',
            'fantasy_points_calculated' => 'puntos_calculados',
        ]);
        // status es varchar con default → SQL crudo para evitar bug de escape
        if (Schema::hasTable('race_results') && Schema::hasColumn('race_results', 'status')) {
            DB::statement("ALTER TABLE race_results CHANGE `status` `estado` VARCHAR(255) NOT NULL DEFAULT 'Finalizó'");
        }
        $this->renameTable('race_results', 'resultados_carrera');

        // ─── SCORING_RULES → REGLAS_PUNTUACION ──────────────────────────────
        $this->renameColumns('scoring_rules', [
            'event'       => 'evento',
            'points'      => 'puntos',
            'description' => 'descripcion',
            'is_active'   => 'activa',
        ]);
        $this->renameTable('scoring_rules', 'reglas_puntuacion');

        // ─── LEAGUES → LIGAS ────────────────────────────────────────────────
        $this->renameColumns('leagues', [
            'name'        => 'nombre',
            'description' => 'descripcion',
            'code'        => 'codigo',
            'season'      => 'temporada',
            'is_private'  => 'es_privada',
            'max_members' => 'max_miembros',
            'owner_id'    => 'propietario_id',
        ]);
        if (Schema::hasTable('leagues') && Schema::hasColumn('leagues', 'status')) {
            DB::statement("ALTER TABLE leagues CHANGE `status` `estado` ENUM('active','finished','draft') NOT NULL DEFAULT 'draft'");
        }
        $this->renameTable('leagues', 'ligas');

        // ─── LEAGUE_MEMBERS → MIEMBROS_LIGA ─────────────────────────────────
        $this->renameColumns('league_members', [
            'league_id'    => 'liga_id',
            'user_id'      => 'usuario_id',
            'joined_at'    => 'fecha_union',
            'total_points' => 'puntos_totales',
            'rank'         => 'posicion',
        ]);
        $this->renameTable('league_members', 'miembros_liga');

        // ─── FANTASY_TEAMS → EQUIPOS_FANTASY ────────────────────────────────
        $this->renameColumns('fantasy_teams', [
            'user_id'          => 'usuario_id',
            'league_id'        => 'liga_id',
            'name'             => 'nombre',
            'remaining_budget' => 'presupuesto_restante',
            'total_points'     => 'puntos_totales',
        ]);
        $this->renameTable('fantasy_teams', 'equipos_fantasy');

        // ─── FANTASY_TEAM_DRIVERS → EQUIPOS_FANTASY_PILOTOS ─────────────────
        $this->renameColumns('fantasy_team_drivers', [
            'fantasy_team_id' => 'equipo_fantasy_id',
            'driver_id'       => 'piloto_id',
            'selected_at'     => 'fecha_seleccion',
            'removed_at'      => 'fecha_baja',
        ]);
        if (Schema::hasTable('fantasy_team_drivers') && Schema::hasColumn('fantasy_team_drivers', 'role')) {
            DB::statement("ALTER TABLE fantasy_team_drivers CHANGE `role` `rol` ENUM('titular','banquillo') NOT NULL DEFAULT 'titular'");
        }
        $this->renameTable('fantasy_team_drivers', 'equipos_fantasy_pilotos');

        // ─── FANTASY_TEAM_CONSTRUCTORS → EQUIPOS_FANTASY_ESCUDERIAS ─────────
        $this->renameColumns('fantasy_team_constructors', [
            'fantasy_team_id' => 'equipo_fantasy_id',
            'constructor_id'  => 'escuderia_id',
            'selected_at'     => 'fecha_seleccion',
            'removed_at'      => 'fecha_baja',
        ]);
        $this->renameTable('fantasy_team_constructors', 'equipos_fantasy_escuderias');

        // ─── FANTASY_TEAM_PRINCIPALS → EQUIPOS_FANTASY_DIRECTORES ───────────
        $this->renameColumns('fantasy_team_principals', [
            'fantasy_team_id'   => 'equipo_fantasy_id',
            'team_principal_id' => 'director_id',
            'selected_at'       => 'fecha_seleccion',
            'removed_at'        => 'fecha_baja',
        ]);
        $this->renameTable('fantasy_team_principals', 'equipos_fantasy_directores');

        // ─── FANTASY_TEAM_RACE_POINTS → PUNTOS_EQUIPO_CARRERA ───────────────
        $this->renameColumns('fantasy_team_race_points', [
            'fantasy_team_id' => 'equipo_fantasy_id',
            'race_id'         => 'carrera_id',
            'points_earned'   => 'puntos_obtenidos',
            'breakdown'       => 'desglose',
        ]);
        $this->renameTable('fantasy_team_race_points', 'puntos_equipo_carrera');
    }

    public function down(): void
    {
        // No-op: usar drop/recreate si hace falta volver al estado anterior.
    }

    /**
     * Renombra columnas sólo si la tabla y la columna original existen.
     */
    private function renameColumns(string $tabla, array $renombres): void
    {
        if (!Schema::hasTable($tabla)) return;

        Schema::table($tabla, function (Blueprint $t) use ($tabla, $renombres) {
            foreach ($renombres as $antiguo => $nuevo) {
                if (Schema::hasColumn($tabla, $antiguo) && !Schema::hasColumn($tabla, $nuevo)) {
                    $t->renameColumn($antiguo, $nuevo);
                }
            }
        });
    }

    /**
     * Renombra una tabla sólo si la antigua existe y la nueva no.
     */
    private function renameTable(string $antigua, string $nueva): void
    {
        if (Schema::hasTable($antigua) && !Schema::hasTable($nueva)) {
            Schema::rename($antigua, $nueva);
        }
    }
};
