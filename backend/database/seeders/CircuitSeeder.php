<?php

namespace Database\Seeders;

use App\Models\Circuito;
use Illuminate\Database\Seeder;

class CircuitSeeder extends Seeder
{
    public function run(): void
    {
        $circuitos = [
            ['api_id' => 'bahrain',       'nombre' => 'Bahrain International Circuit',      'ubicacion' => 'Sakhir',      'pais' => 'Baréin'],
            ['api_id' => 'jeddah',        'nombre' => 'Jeddah Corniche Circuit',            'ubicacion' => 'Yeda',        'pais' => 'Arabia Saudí'],
            ['api_id' => 'albert_park',   'nombre' => 'Albert Park Grand Prix Circuit',     'ubicacion' => 'Melbourne',   'pais' => 'Australia'],
            ['api_id' => 'suzuka',        'nombre' => 'Suzuka International Racing Course', 'ubicacion' => 'Suzuka',      'pais' => 'Japón'],
            ['api_id' => 'shanghai',      'nombre' => 'Shanghai International Circuit',     'ubicacion' => 'Shanghái',    'pais' => 'China'],
            ['api_id' => 'miami',         'nombre' => 'Miami International Autodrome',      'ubicacion' => 'Miami',       'pais' => 'Estados Unidos'],
            ['api_id' => 'imola',         'nombre' => 'Autodromo Enzo e Dino Ferrari',      'ubicacion' => 'Imola',       'pais' => 'Italia'],
            ['api_id' => 'monaco',        'nombre' => 'Circuito de Mónaco',                 'ubicacion' => 'Montecarlo',  'pais' => 'Mónaco'],
            ['api_id' => 'villeneuve',    'nombre' => 'Circuito Gilles Villeneuve',         'ubicacion' => 'Montreal',    'pais' => 'Canadá'],
            ['api_id' => 'catalunya',     'nombre' => 'Circuit de Barcelona-Catalunya',     'ubicacion' => 'Montmeló',    'pais' => 'España'],
            ['api_id' => 'red_bull_ring', 'nombre' => 'Red Bull Ring',                      'ubicacion' => 'Spielberg',   'pais' => 'Austria'],
            ['api_id' => 'silverstone',   'nombre' => 'Silverstone Circuit',                'ubicacion' => 'Silverstone', 'pais' => 'Reino Unido'],
            ['api_id' => 'hungaroring',   'nombre' => 'Hungaroring',                        'ubicacion' => 'Budapest',    'pais' => 'Hungría'],
            ['api_id' => 'spa',           'nombre' => 'Circuito de Spa-Francorchamps',      'ubicacion' => 'Spa',         'pais' => 'Bélgica'],
            ['api_id' => 'zandvoort',     'nombre' => 'Circuit Zandvoort',                  'ubicacion' => 'Zandvoort',   'pais' => 'Países Bajos'],
            ['api_id' => 'monza',         'nombre' => 'Autodromo Nazionale di Monza',       'ubicacion' => 'Monza',       'pais' => 'Italia'],
            ['api_id' => 'baku',          'nombre' => 'Circuito Urbano de Bakú',            'ubicacion' => 'Bakú',        'pais' => 'Azerbaiyán'],
            ['api_id' => 'marina_bay',    'nombre' => 'Marina Bay Street Circuit',          'ubicacion' => 'Singapur',    'pais' => 'Singapur'],
            ['api_id' => 'americas',      'nombre' => 'Circuito de las Américas',           'ubicacion' => 'Austin',      'pais' => 'Estados Unidos'],
            ['api_id' => 'rodriguez',     'nombre' => 'Autódromo Hermanos Rodríguez',       'ubicacion' => 'Ciudad de México', 'pais' => 'México'],
            ['api_id' => 'interlagos',    'nombre' => 'Autódromo José Carlos Pace',         'ubicacion' => 'São Paulo',   'pais' => 'Brasil'],
            ['api_id' => 'vegas',         'nombre' => 'Las Vegas Strip Street Circuit',     'ubicacion' => 'Las Vegas',   'pais' => 'Estados Unidos'],
            ['api_id' => 'losail',        'nombre' => 'Losail International Circuit',       'ubicacion' => 'Al Daayen',   'pais' => 'Catar'],
            ['api_id' => 'yas_marina',    'nombre' => 'Yas Marina Circuit',                 'ubicacion' => 'Abu Dabi',    'pais' => 'Emiratos Árabes Unidos'],
        ];

        foreach ($circuitos as $c) {
            Circuito::updateOrCreate(['api_id' => $c['api_id']], $c);
        }
    }
}
