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
            ['name' => 'Christian Horner',   'nationality' => 'British',   'constructor' => 'red_bull',    'price' => 25_000_000],
            ['name' => 'Frédéric Vasseur',   'nationality' => 'French',    'constructor' => 'ferrari',     'price' => 22_000_000],
            ['name' => 'Toto Wolff',         'nationality' => 'Austrian',  'constructor' => 'mercedes',    'price' => 20_000_000],
            ['name' => 'Andrea Stella',      'nationality' => 'Italian',   'constructor' => 'mclaren',     'price' => 20_000_000],
            ['name' => 'Andy Cowell',        'nationality' => 'British',   'constructor' => 'aston_martin','price' => 12_000_000],
            ['name' => 'Oliver Oakes',       'nationality' => 'British',   'constructor' => 'alpine',      'price' => 10_000_000],
            ['name' => 'James Vowles',       'nationality' => 'British',   'constructor' => 'williams',    'price' => 10_000_000],
            ['name' => 'Laurent Mekies',     'nationality' => 'French',    'constructor' => 'rb',          'price' =>  8_000_000],
            ['name' => 'Ayao Komatsu',       'nationality' => 'Japanese',  'constructor' => 'haas',        'price' =>  8_000_000],
            ['name' => 'Mattia Binotto',     'nationality' => 'Italian',   'constructor' => 'sauber',      'price' =>  8_000_000],
        ];

        foreach ($directores as $datos) {
            $escuderia = Escuderia::where('api_id', $datos['constructor'])->first();

            DirectorEquipo::updateOrCreate(
                ['name' => $datos['name']],
                [
                    'nationality'    => $datos['nationality'],
                    'constructor_id' => $escuderia?->id,
                    'price'          => $datos['price'],
                    'is_active'      => true,
                ]
            );
        }
    }
}
