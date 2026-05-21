<?php

namespace Database\Seeders;

use App\Models\Escuderia;
use App\Models\Piloto;
use App\Models\ReglaPuntuacion;
use App\Models\User;
use App\Services\ServicioApiF1;
use App\Support\Traducciones;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador
        User::updateOrCreate(
            ['email' => 'admin@f1fantasy.local'],
            [
                'nombre'   => 'Admin F1 Fantasy',
                'usuario'  => 'admin',
                'password' => Hash::make('admin1234'),
                'rol'      => 'admin',
            ]
        );

        // Usuario de prueba
        User::updateOrCreate(
            ['email' => 'usuario@f1fantasy.local'],
            [
                'nombre'   => 'Usuario Prueba',
                'usuario'  => 'usuario',
                'password' => Hash::make('usuario1234'),
                'rol'      => 'user',
            ]
        );

        // Reglas de puntuación fantasy
        $reglas = [
            // ── Posición final en carrera (puntos_carrera) ───────────────────────
            ['evento' => 'FINISH_P1',  'puntos' => 25, 'descripcion' => '1ª posición en carrera'],
            ['evento' => 'FINISH_P2',  'puntos' => 18, 'descripcion' => '2ª posición en carrera'],
            ['evento' => 'FINISH_P3',  'puntos' => 15, 'descripcion' => '3ª posición en carrera'],
            ['evento' => 'FINISH_P4',  'puntos' => 12, 'descripcion' => '4ª posición en carrera'],
            ['evento' => 'FINISH_P5',  'puntos' => 10, 'descripcion' => '5ª posición en carrera'],
            ['evento' => 'FINISH_P6',  'puntos' =>  8, 'descripcion' => '6ª posición en carrera'],
            ['evento' => 'FINISH_P7',  'puntos' =>  6, 'descripcion' => '7ª posición en carrera'],
            ['evento' => 'FINISH_P8',  'puntos' =>  4, 'descripcion' => '8ª posición en carrera'],
            ['evento' => 'FINISH_P9',  'puntos' =>  2, 'descripcion' => '9ª posición en carrera'],
            ['evento' => 'FINISH_P10', 'puntos' =>  1, 'descripcion' => '10ª posición en carrera'],

            // ── Bonificaciones de carrera (puntos_carrera) ───────────────────────
            ['evento' => 'FASTEST_LAP',       'puntos' => 5, 'descripcion' => 'Vuelta rápida'],
            ['evento' => 'OVERTAKES_3',        'puntos' => 3, 'descripcion' => 'Supera a 3-4 coches en carrera'],
            ['evento' => 'OVERTAKES_5',        'puntos' => 5, 'descripcion' => 'Supera a 5+ coches en carrera'],
            ['evento' => 'BEATS_TEAMMATE',     'puntos' => 3, 'descripcion' => 'Supera al compañero en carrera'],

            // ── Penalizaciones de carrera (puntos_carrera) ───────────────────────
            ['evento' => 'DNF',          'puntos' => -15, 'descripcion' => 'No termina la carrera'],
            ['evento' => 'DNS',          'puntos' => -20, 'descripcion' => 'No sale en carrera'],
            ['evento' => 'PENALTY_GRID', 'puntos' =>  -5, 'descripcion' => 'Penalización de grid'],
            ['evento' => 'PENALTY_TIME', 'puntos' =>  -5, 'descripcion' => 'Penalización de tiempo'],
            ['evento' => 'DISQUALIFIED', 'puntos' => -25, 'descripcion' => 'Descalificado'],

            // ── Posición en clasificación (puntos_qualy) ─────────────────────────
            ['evento' => 'QUALI_P1',  'puntos' => 10, 'descripcion' => 'Pole position'],
            ['evento' => 'QUALI_P2',  'puntos' =>  9, 'descripcion' => '2ª en clasificación'],
            ['evento' => 'QUALI_P3',  'puntos' =>  8, 'descripcion' => '3ª en clasificación'],
            ['evento' => 'QUALI_P4',  'puntos' =>  7, 'descripcion' => '4ª en clasificación'],
            ['evento' => 'QUALI_P5',  'puntos' =>  6, 'descripcion' => '5ª en clasificación'],
            ['evento' => 'QUALI_P6',  'puntos' =>  5, 'descripcion' => '6ª en clasificación'],
            ['evento' => 'QUALI_P7',  'puntos' =>  4, 'descripcion' => '7ª en clasificación'],
            ['evento' => 'QUALI_P8',  'puntos' =>  3, 'descripcion' => '8ª en clasificación'],
            ['evento' => 'QUALI_P9',  'puntos' =>  2, 'descripcion' => '9ª en clasificación'],
            ['evento' => 'QUALI_P10', 'puntos' =>  1, 'descripcion' => '10ª en clasificación'],

            // ── Bonificaciones de qualy (puntos_qualy) ───────────────────────────
            ['evento' => 'BEATS_TEAMMATE_QUALY', 'puntos' => 3, 'descripcion' => 'Supera al compañero en clasificación'],
        ];

        // Eliminar reglas obsoletas
        \App\Models\ReglaPuntuacion::whereIn('evento', ['DRIVER_OF_DAY'])->delete();

        foreach ($reglas as $regla) {
            ReglaPuntuacion::updateOrCreate(
                ['evento' => $regla['evento']],
                $regla
            );
        }

        $this->call([CircuitSeeder::class]);

        // Sincronizar escuderías, pilotos y carreras desde la API de F1
        // (necesario antes de CochesSeeder y MediaImagenesSeeder)
        $this->command->info('Sincronizando datos desde la API de F1...');
        try {
            $api = app(ServicioApiF1::class);
            $escuderias = $api->sincronizarEscuderias();
            $pilotos    = $api->sincronizarPilotos();
            $carreras   = $api->sincronizarCarreras();
            $this->command->info("  ✓ {$escuderias} escuderías, {$pilotos} pilotos, {$carreras} carreras");
        } catch (\Throwable $e) {
            $this->command->warn('  ⚠ No se pudo conectar con la API de F1: ' . $e->getMessage());
            $this->command->warn('  Ejecuta manualmente después: php artisan db:seed --class=CochesSeeder');
            return;
        }

        // Equipos nuevos 2026 que la API aún puede no devolver — creamos si no existen
        $this->asegurarEquipos2026();

        $this->call([
            CochesSeeder::class,
            MediaImagenesSeeder::class,
        ]);
    }

    /**
     * Crea los equipos y pilotos de 2026 que la API de Ergast aún no devuelve.
     * Si ya existen (porque la API los devolvió), updateOrCreate los deja intactos.
     */
    private function asegurarEquipos2026(): void
    {
        // ── Audi F1 Team ──────────────────────────────────────────────────────
        $audi = Escuderia::updateOrCreate(
            ['api_id' => 'audi'],
            ['nombre' => 'Audi F1 Team', 'nacionalidad' => 'Alemana', 'activa' => true]
        );

        Piloto::updateOrCreate(
            ['api_id' => 'hulkenberg'],
            ['nombre' => 'Nico', 'apellido' => 'Hülkenberg', 'codigo' => 'HUL',
             'numero' => 27, 'nacionalidad' => 'Alemana', 'activo' => true,
             'es_reserva' => false, 'escuderia_id' => $audi->id]
        );
        Piloto::updateOrCreate(
            ['api_id' => 'bortoleto'],
            ['nombre' => 'Gabriel', 'apellido' => 'Bortoleto', 'codigo' => 'BOR',
             'numero' => 5, 'nacionalidad' => 'Brasileña', 'activo' => true,
             'es_reserva' => false, 'escuderia_id' => $audi->id]
        );

        // ── Cadillac F1 Team ──────────────────────────────────────────────────
        $cadillac = Escuderia::updateOrCreate(
            ['api_id' => 'cadillac'],
            ['nombre' => 'Cadillac F1 Team', 'nacionalidad' => 'Estadounidense', 'activa' => true]
        );

        Piloto::updateOrCreate(
            ['api_id' => 'bottas'],
            ['nombre' => 'Valtteri', 'apellido' => 'Bottas', 'codigo' => 'BOT',
             'numero' => 77, 'nacionalidad' => 'Finlandesa', 'activo' => true,
             'es_reserva' => false, 'escuderia_id' => $cadillac->id]
        );
        Piloto::updateOrCreate(
            ['api_id' => 'perez'],
            ['nombre' => 'Sergio', 'apellido' => 'Pérez', 'codigo' => 'PER',
             'numero' => 11, 'nacionalidad' => 'Mexicana', 'activo' => true,
             'es_reserva' => false, 'escuderia_id' => $cadillac->id]
        );
    }
}
