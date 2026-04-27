<?php

namespace Database\Seeders;

use App\Models\Circuito;
use Illuminate\Database\Seeder;

class CircuitSeeder extends Seeder
{
    public function run(): void
    {
        $circuits = [
            ['api_id' => 'bahrain',       'name' => 'Bahrain International Circuit',       'location' => 'Sakhir',      'country' => 'Bahrain'],
            ['api_id' => 'jeddah',        'name' => 'Jeddah Corniche Circuit',              'location' => 'Jeddah',      'country' => 'Saudi Arabia'],
            ['api_id' => 'albert_park',   'name' => 'Albert Park Grand Prix Circuit',      'location' => 'Melbourne',   'country' => 'Australia'],
            ['api_id' => 'suzuka',        'name' => 'Suzuka International Racing Course',  'location' => 'Suzuka',      'country' => 'Japan'],
            ['api_id' => 'shanghai',      'name' => 'Shanghai International Circuit',      'location' => 'Shanghai',    'country' => 'China'],
            ['api_id' => 'miami',         'name' => 'Miami International Autodrome',       'location' => 'Miami',       'country' => 'USA'],
            ['api_id' => 'imola',         'name' => 'Autodromo Enzo e Dino Ferrari',       'location' => 'Imola',       'country' => 'Italy'],
            ['api_id' => 'monaco',        'name' => 'Circuit de Monaco',                   'location' => 'Monte-Carlo', 'country' => 'Monaco'],
            ['api_id' => 'villeneuve',    'name' => 'Circuit Gilles Villeneuve',            'location' => 'Montreal',    'country' => 'Canada'],
            ['api_id' => 'catalunya',     'name' => 'Circuit de Barcelona-Catalunya',      'location' => 'Montmeló',    'country' => 'Spain'],
            ['api_id' => 'red_bull_ring', 'name' => 'Red Bull Ring',                       'location' => 'Spielberg',   'country' => 'Austria'],
            ['api_id' => 'silverstone',   'name' => 'Silverstone Circuit',                 'location' => 'Silverstone', 'country' => 'UK'],
            ['api_id' => 'hungaroring',   'name' => 'Hungaroring',                         'location' => 'Budapest',    'country' => 'Hungary'],
            ['api_id' => 'spa',           'name' => 'Circuit de Spa-Francorchamps',        'location' => 'Spa',         'country' => 'Belgium'],
            ['api_id' => 'zandvoort',     'name' => 'Circuit Zandvoort',                   'location' => 'Zandvoort',   'country' => 'Netherlands'],
            ['api_id' => 'monza',         'name' => 'Autodromo Nazionale di Monza',        'location' => 'Monza',       'country' => 'Italy'],
            ['api_id' => 'baku',          'name' => 'Baku City Circuit',                   'location' => 'Baku',        'country' => 'Azerbaijan'],
            ['api_id' => 'marina_bay',    'name' => 'Marina Bay Street Circuit',           'location' => 'Singapore',   'country' => 'Singapore'],
            ['api_id' => 'americas',      'name' => 'Circuit of the Americas',             'location' => 'Austin',      'country' => 'USA'],
            ['api_id' => 'rodriguez',     'name' => 'Autodromo Hermanos Rodriguez',        'location' => 'Mexico City', 'country' => 'Mexico'],
            ['api_id' => 'interlagos',    'name' => 'Autodromo Jose Carlos Pace',          'location' => 'São Paulo',   'country' => 'Brazil'],
            ['api_id' => 'vegas',         'name' => 'Las Vegas Strip Street Circuit',      'location' => 'Las Vegas',   'country' => 'USA'],
            ['api_id' => 'losail',        'name' => 'Losail International Circuit',        'location' => 'Al Daayen',   'country' => 'Qatar'],
            ['api_id' => 'yas_marina',    'name' => 'Yas Marina Circuit',                  'location' => 'Abu Dhabi',   'country' => 'UAE'],
        ];

        foreach ($circuits as $circuit) {
            Circuito::updateOrCreate(['api_id' => $circuit['api_id']], $circuit);
        }
    }
}
