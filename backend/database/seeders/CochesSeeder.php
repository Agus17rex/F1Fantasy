<?php

namespace Database\Seeders;

use App\Models\Coche;
use App\Models\Escuderia;
use Illuminate\Database\Seeder;

/**
 * Crea un coche por escudería 2026.
 *
 * Los nombres usan el código de modelo oficial cuando se conoce,
 * o un genérico "{escudería} 2026". El precio se vincula al de la
 * escudería (mismo orden de magnitud) — se recalcula con la API.
 */
class CochesSeeder extends Seeder
{
    public function run(): void
    {
        // Una entrada por cada api_id de escudería que la API de F1 devuelve.
        // Si la API añade nuevas (audi, cadillac…) en 2026, basta con añadirlas aquí.
        $coches = [
            'red_bull'     => ['nombre' => 'RB21',     'precio' => 25_000_000],
            'ferrari'      => ['nombre' => 'SF-25',    'precio' => 22_000_000],
            'mercedes'     => ['nombre' => 'W16',      'precio' => 20_000_000],
            'mclaren'      => ['nombre' => 'MCL39',    'precio' => 23_000_000],
            'aston_martin' => ['nombre' => 'AMR25',    'precio' => 8_000_000],
            'alpine'       => ['nombre' => 'A525',     'precio' => 12_000_000],
            'williams'     => ['nombre' => 'FW47',     'precio' => 13_000_000],
            'rb'           => ['nombre' => 'VCARB 02', 'precio' => 11_000_000],
            'haas'         => ['nombre' => 'VF-25',    'precio' => 10_000_000],
            'audi'         => ['nombre' => 'Audi R26', 'precio' => 11_000_000],
            'cadillac'     => ['nombre' => 'Cadillac C26', 'precio' => 9_000_000],
        ];

        foreach ($coches as $apiId => $datos) {
            $escuderia = Escuderia::where('api_id', $apiId)->first();
            if (!$escuderia) continue;

            Coche::updateOrCreate(
                ['escuderia_id' => $escuderia->id],
                [
                    'nombre' => $datos['nombre'],
                    'precio' => $datos['precio'],
                    'activo' => true,
                ]
            );
        }
    }
}
