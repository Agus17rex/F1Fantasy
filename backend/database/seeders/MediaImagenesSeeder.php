<?php

namespace Database\Seeders;

use App\Models\Coche;
use App\Models\Escuderia;
use App\Models\Piloto;
use Illuminate\Database\Seeder;

/**
 * Rellena los campos `foto` (pilotos / coches) y `logo` (escuderías)
 * con URLs oficiales de formula1.com para el grid 2026.
 */
class MediaImagenesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPilotos();
        $this->seedEscuderias();
        $this->seedCoches();
    }

    private function seedPilotos(): void
    {

        $base = 'https://media.formula1.com/image/upload/c_lfill,w_520/q_auto/d_common:f1:2026:fallback:driver:2026fallbackdriverright.webp/v1740000001/common/f1/2026/';
        $fotos = [
            'albon'          => $base . "williams/alealb01/2026williamsalealb01right.webp",
            'alonso'         => $base . "astonmartin/feralo01/2026astonmartinferalo01right.webp",
            'antonelli'      => $base . "mercedes/andant01/2026mercedesandant01right.webp",
            'bearman'        => $base . "haasf1team/olibea01/2026haasf1teamolibea01right.webp",
            'bortoleto'      => $base . "audi/gabbor01/2026audigabbor01right.webp",
            'bottas'         => $base . "cadillac/valbot01/2026cadillacvalbot01right.webp",
            'colapinto'      => $base . "alpine/fracol01/2026alpinefracol01right.webp",
            'gasly'          => $base . "alpine/piegas01/2026alpinepiegas01right.webp",
            'hadjar'         => $base . "redbullracing/isahad01/2026redbullracingisahad01right.webp",
            'hamilton'       => $base . "ferrari/lewham01/2026ferrarilewham01right.webp",
            'hulkenberg'     => $base . "audi/nichul01/2026audinichul01right.webp",
            'lawson'         => $base . "racingbulls/lialaw01/2026racingbullslialaw01right.webp",
            'leclerc'        => $base . "ferrari/chalec01/2026ferrarichalec01right.webp",
            'arvid_lindblad' => $base . "racingbulls/arvlin01/2026racingbullsarvlin01right.webp",
            'norris'         => $base . "mclaren/lannor01/2026mclarenlannor01right.webp",
            'ocon'           => $base . "haasf1team/estoco01/2026haasf1teamestoco01right.webp",
            'perez'          => $base . "cadillac/serper01/2026cadillacserper01right.webp",
            'piastri'        => $base . "mclaren/oscpia01/2026mclarenoscpia01right.webp",
            'russell'        => $base . "mercedes/georus01/2026mercedesgeorus01right.webp",
            'sainz'          => $base . "williams/carsai01/2026williamscarsai01right.webp",
            'stroll'         => $base . "astonmartin/lanstr01/2026astonmartinlanstr01right.webp",
            'max_verstappen' => $base . "redbullracing/maxver01/2026redbullracingmaxver01right.webp"
        ];

        foreach ($fotos as $apiId => $url) {
            Piloto::where('api_id', $apiId)->update(['foto' => $url]);
        }
    }

    private function seedEscuderias(): void
    {
        $base = 'https://media.formula1.com/image/upload/c_lfill,w_520/q_auto/v1740000001/common/f1/2026/';

        $datos = [
            'alpine'       => ['logo' => $base . 'alpine/2026alpinelogo.webp',                 'color' => '#FF87BC'],
            'aston_martin' => ['logo' => $base . 'astonmartin/2026astonmartinlogo.webp',       'color' => '#229971'],
            'ferrari'      => ['logo' => $base . 'ferrari/2026ferrarilogo.webp',               'color' => '#E8002D'],
            'haas'         => ['logo' => $base . 'haasf1team/2026haasf1teamlogo.webp',         'color' => '#B6BABD'],
            'mclaren'      => ['logo' => $base . 'mclaren/2026mclarenlogo.webp',               'color' => '#FF8000'],
            'mercedes'     => ['logo' => $base . 'mercedes/2026mercedeslogo.webp',             'color' => '#27F4D2'],
            'rb'           => ['logo' => $base . 'racingbulls/2026racingbullslogo.webp',       'color' => '#6692FF'],
            'red_bull'     => ['logo' => $base . 'redbullracing/2026redbullracinglogo.webp',   'color' => '#3671C6'],
            'williams'     => ['logo' => $base . 'williams/2026williamslogo.webp',             'color' => '#64C4FF'],
            'audi'         => ['logo' => $base . 'audi/2026audilogowhite.webp',                'color' => '#C0C0C0'],
            'cadillac'     => ['logo' => $base . 'cadillac/2026cadillaclogowhite.webp',        'color' => '#C8A848'],
        ];

        foreach ($datos as $apiId => $info) {
            Escuderia::where('api_id', $apiId)->update(['logo' => $info['logo'], 'color' => $info['color']]);
        }
    }

    private function seedCoches(): void
    {
        // Foto del coche por escudería (api_id) — rellénalas tú.
        // Si una URL queda vacía o falla, el frontend mostrará el icono 🏎️.

        $base = "https://media.formula1.com/image/upload/c_lfill,w_512/q_auto/d_common:f1:2026:fallback:car:2026fallbackcarright.webp/v1740000001/common/f1/2026/";
        $fotos = [
            'red_bull'     => $base . 'redbullracing/2026redbullracingcarright.webp',
            'ferrari'      => $base . 'ferrari/2026ferraricarright.webp',
            'mercedes'     => $base . 'mercedes/2026mercedescarright.webp',
            'mclaren'      => $base . 'mclaren/2026mclarencarright.webp',
            'aston_martin' => $base . 'astonmartin/2026astonmartincarright.webp',
            'alpine'       => $base . 'alpine/2026alpinecarright.webp',
            'williams'     => $base . 'williams/2026williamscarright.webp',
            'rb'           => $base . 'racingbulls/2026racingbullscarright.webp',
            'haas'         => $base . 'haasf1team/2026haasf1teamcarright.webp',
            'audi'         => $base . 'audi/2026audicarright.webp',
            'cadillac'     => $base . 'cadillac/2026cadillaccarright.webp',
        ];

        foreach ($fotos as $apiId => $url) {
            $escuderia = Escuderia::where('api_id', $apiId)->first();
            if (!$escuderia) continue;

            Coche::where('escuderia_id', $escuderia->id)
                ->update(['foto' => $url ?: null]);
        }
    }
}
