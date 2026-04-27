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
        // Usuario administrador — updateOrCreate evita el error si ya existe
        User::updateOrCreate(
            ['email' => 'admin@f1fantasy.local'],
            [
                'name'     => 'Admin F1 Fantasy',
                'username' => 'admin',
                'password' => Hash::make('admin1234'),
                'role'     => 'admin',
            ]
        );

        // Usuario de prueba
        User::updateOrCreate(
            ['email' => 'usuario@f1fantasy.local'],
            [
                'name'     => 'Usuario Prueba',
                'username' => 'usuario',
                'password' => Hash::make('usuario1234'),
                'role'     => 'user',
            ]
        );

        // Reglas de puntuación fantasy
        $reglas = [
            // Posición final en carrera
            ['event' => 'FINISH_P1',  'points' => 25,  'description' => '1ª posición en carrera'],
            ['event' => 'FINISH_P2',  'points' => 18,  'description' => '2ª posición en carrera'],
            ['event' => 'FINISH_P3',  'points' => 15,  'description' => '3ª posición en carrera'],
            ['event' => 'FINISH_P4',  'points' => 12,  'description' => '4ª posición en carrera'],
            ['event' => 'FINISH_P5',  'points' => 10,  'description' => '5ª posición en carrera'],
            ['event' => 'FINISH_P6',  'points' => 8,   'description' => '6ª posición en carrera'],
            ['event' => 'FINISH_P7',  'points' => 6,   'description' => '7ª posición en carrera'],
            ['event' => 'FINISH_P8',  'points' => 4,   'description' => '8ª posición en carrera'],
            ['event' => 'FINISH_P9',  'points' => 2,   'description' => '9ª posición en carrera'],
            ['event' => 'FINISH_P10', 'points' => 1,   'description' => '10ª posición en carrera'],

            // Posición en clasificación
            ['event' => 'QUALI_P1',  'points' => 10, 'description' => 'Pole position'],
            ['event' => 'QUALI_P2',  'points' => 9,  'description' => '2ª en clasificación'],
            ['event' => 'QUALI_P3',  'points' => 8,  'description' => '3ª en clasificación'],
            ['event' => 'QUALI_P4',  'points' => 7,  'description' => '4ª en clasificación'],
            ['event' => 'QUALI_P5',  'points' => 6,  'description' => '5ª en clasificación'],

            // Bonificaciones
            ['event' => 'FASTEST_LAP',    'points' => 5,  'description' => 'Vuelta rápida'],
            ['event' => 'DRIVER_OF_DAY',  'points' => 5,  'description' => 'Piloto del día'],
            ['event' => 'OVERTAKES_3',    'points' => 3,  'description' => 'Supera a 3+ coches en carrera'],
            ['event' => 'OVERTAKES_5',    'points' => 5,  'description' => 'Supera a 5+ coches en carrera'],
            ['event' => 'BEATS_TEAMMATE', 'points' => 3,  'description' => 'Supera al compañero de equipo'],

            // Penalizaciones
            ['event' => 'DNF',          'points' => -15, 'description' => 'No termina la carrera'],
            ['event' => 'DNS',          'points' => -20, 'description' => 'No sale en carrera'],
            ['event' => 'PENALTY_GRID', 'points' => -5,  'description' => 'Penalización de grid'],
            ['event' => 'PENALTY_TIME', 'points' => -5,  'description' => 'Penalización de tiempo'],
            ['event' => 'DISQUALIFIED', 'points' => -25, 'description' => 'Descalificado'],
        ];

        foreach ($reglas as $regla) {
            ReglaPuntuacion::updateOrCreate(
                ['event' => $regla['event']],
                $regla
            );
        }

        $this->call([
            CircuitSeeder::class,
            DirectoresSeeder::class,
        ]);
    }
}
