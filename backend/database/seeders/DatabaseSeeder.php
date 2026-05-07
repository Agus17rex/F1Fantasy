<?php

namespace Database\Seeders;

use App\Models\ReglaPuntuacion;
use App\Models\User;
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
            // Posición final en carrera
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

            // Posición en clasificación
            ['evento' => 'QUALI_P1', 'puntos' => 10, 'descripcion' => 'Pole position'],
            ['evento' => 'QUALI_P2', 'puntos' =>  9, 'descripcion' => '2ª en clasificación'],
            ['evento' => 'QUALI_P3', 'puntos' =>  8, 'descripcion' => '3ª en clasificación'],
            ['evento' => 'QUALI_P4', 'puntos' =>  7, 'descripcion' => '4ª en clasificación'],
            ['evento' => 'QUALI_P5', 'puntos' =>  6, 'descripcion' => '5ª en clasificación'],

            // Bonificaciones
            ['evento' => 'FASTEST_LAP',    'puntos' => 5, 'descripcion' => 'Vuelta rápida'],
            ['evento' => 'DRIVER_OF_DAY',  'puntos' => 5, 'descripcion' => 'Piloto del día'],
            ['evento' => 'OVERTAKES_3',    'puntos' => 3, 'descripcion' => 'Supera a 3+ coches en carrera'],
            ['evento' => 'OVERTAKES_5',    'puntos' => 5, 'descripcion' => 'Supera a 5+ coches en carrera'],
            ['evento' => 'BEATS_TEAMMATE', 'puntos' => 3, 'descripcion' => 'Supera al compañero de equipo'],

            // Penalizaciones
            ['evento' => 'DNF',          'puntos' => -15, 'descripcion' => 'No termina la carrera'],
            ['evento' => 'DNS',          'puntos' => -20, 'descripcion' => 'No sale en carrera'],
            ['evento' => 'PENALTY_GRID', 'puntos' =>  -5, 'descripcion' => 'Penalización de grid'],
            ['evento' => 'PENALTY_TIME', 'puntos' =>  -5, 'descripcion' => 'Penalización de tiempo'],
            ['evento' => 'DISQUALIFIED', 'puntos' => -25, 'descripcion' => 'Descalificado'],
        ];

        foreach ($reglas as $regla) {
            ReglaPuntuacion::updateOrCreate(
                ['evento' => $regla['evento']],
                $regla
            );
        }

        $this->call([
            CircuitSeeder::class,
        ]);

        // ⚠️  CochesSeeder, EliminarSauberSeeder y MediaImagenesSeeder requieren
        //     que las escuderías ya estén en BD (ejecutar sync desde el panel admin primero).
        //     Lánzalos manualmente después de sincronizar:
        //       php artisan db:seed --class=CochesSeeder
        //       php artisan db:seed --class=EliminarSauberSeeder
        //       php artisan db:seed --class=MediaImagenesSeeder
    }
}
