<?php

namespace Database\Seeders;

use App\Models\DirectorEquipo;
use App\Models\Escuderia;
use Illuminate\Database\Seeder;

class DirectoresSeeder extends Seeder
{
    public function run(): void
    {
        // Directores F1 2025 — se vinculan a su escudería por api_id
        $directores = [
            ['nombre' => 'Christian Horner',   'nacionalidad' => 'Británico',  'escuderia' => 'red_bull',    'precio' => 25_000_000],
            ['nombre' => 'Frédéric Vasseur',   'nacionalidad' => 'Francés',    'escuderia' => 'ferrari',     'precio' => 22_000_000],
            ['nombre' => 'Toto Wolff',         'nacionalidad' => 'Austriaco',  'escuderia' => 'mercedes',    'precio' => 20_000_000],
            ['nombre' => 'Andrea Stella',      'nacionalidad' => 'Italiano',   'escuderia' => 'mclaren',     'precio' => 20_000_000],
            ['nombre' => 'Andy Cowell',        'nacionalidad' => 'Británico',  'escuderia' => 'aston_martin','precio' => 12_000_000],
            ['nombre' => 'Oliver Oakes',       'nacionalidad' => 'Británico',  'escuderia' => 'alpine',      'precio' => 10_000_000],
            ['nombre' => 'James Vowles',       'nacionalidad' => 'Británico',  'escuderia' => 'williams',    'precio' => 10_000_000],
            ['nombre' => 'Laurent Mekies',     'nacionalidad' => 'Francés',    'escuderia' => 'rb',          'precio' =>  8_000_000],
            ['nombre' => 'Ayao Komatsu',       'nacionalidad' => 'Japonés',    'escuderia' => 'haas',        'precio' =>  8_000_000],
            ['nombre' => 'Mattia Binotto',     'nacionalidad' => 'Italiano',   'escuderia' => 'sauber',      'precio' =>  8_000_000],
        ];

        foreach ($directores as $datos) {
            $escuderia = Escuderia::where('api_id', $datos['escuderia'])->first();

            DirectorEquipo::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [
                    'nacionalidad' => $datos['nacionalidad'],
                    'escuderia_id' => $escuderia?->id,
                    'precio'       => $datos['precio'],
                    'activo'       => true,
                ]
            );
        }
    }
}
