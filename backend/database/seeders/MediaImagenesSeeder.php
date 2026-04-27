<?php

namespace Database\Seeders;

use App\Models\DirectorEquipo;
use App\Models\Escuderia;
use App\Models\Piloto;
use Illuminate\Database\Seeder;

/**
 * Rellena los campos `foto` (pilotos / directores) y `logo` (escuderías)
 * con URLs oficiales de formula1.com / Wikipedia para el grid 2025.
 */
class MediaImagenesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPilotos();
        $this->seedEscuderias();
        $this->seedDirectores();
    }

    private function seedPilotos(): void
    {
        $base = 'https://media.formula1.com/content/dam/fom-website/drivers';

        $fotos = [
            'albon'          => "$base/A/ALEALB01_Alexander_Albon/alealb01.png.transform/1col/image.png",
            'alonso'         => "$base/F/FERALO01_Fernando_Alonso/feralo01.png.transform/1col/image.png",
            'antonelli'      => "$base/A/ANDANT01_Andrea%20Kimi_Antonelli/andant01.png.transform/1col/image.png",
            'bearman'        => "$base/O/OLIBEA01_Oliver_Bearman/olibea01.png.transform/1col/image.png",
            'bortoleto'      => "$base/G/GABBOR01_Gabriel_Bortoleto/gabbor01.png.transform/1col/image.png",
            'bottas'         => "$base/V/VALBOT01_Valtteri_Bottas/valbot01.png.transform/1col/image.png",
            'colapinto'      => "$base/F/FRACOL01_Franco_Colapinto/fracol01.png.transform/1col/image.png",
            'gasly'          => "$base/P/PIEGAS01_Pierre_Gasly/piegas01.png.transform/1col/image.png",
            'hadjar'         => "$base/I/ISAHAD01_Isack_Hadjar/isahad01.png.transform/1col/image.png",
            'hamilton'       => "$base/L/LEWHAM01_Lewis_Hamilton/lewham01.png.transform/1col/image.png",
            'hulkenberg'     => "$base/N/NICHUL01_Nico_Hulkenberg/nichul01.png.transform/1col/image.png",
            'lawson'         => "$base/L/LIALAW01_Liam_Lawson/lialaw01.png.transform/1col/image.png",
            'leclerc'        => "$base/C/CHALEC01_Charles_Leclerc/chalec01.png.transform/1col/image.png",
            'arvid_lindblad' => "$base/A/ARVLIN01_Arvid_Lindblad/arvlin01.png.transform/1col/image.png",
            'norris'         => "$base/L/LANNOR01_Lando_Norris/lannor01.png.transform/1col/image.png",
            'ocon'           => "$base/E/ESTOCO01_Esteban_Ocon/estoco01.png.transform/1col/image.png",
            'perez'          => "$base/S/SERPER01_Sergio_Perez/serper01.png.transform/1col/image.png",
            'piastri'        => "$base/O/OSCPIA01_Oscar_Piastri/oscpia01.png.transform/1col/image.png",
            'russell'        => "$base/G/GEORUS01_George_Russell/georus01.png.transform/1col/image.png",
            'sainz'          => "$base/C/CARSAI01_Carlos_Sainz/carsai01.png.transform/1col/image.png",
            'stroll'         => "$base/L/LANSTR01_Lance_Stroll/lanstr01.png.transform/1col/image.png",
            'max_verstappen' => "$base/M/MAXVER01_Max_Verstappen/maxver01.png.transform/1col/image.png",
            'tsunoda'        => "$base/Y/YUKTSU01_Yuki_Tsunoda/yuktsu01.png.transform/1col/image.png",
            'doohan'         => "$base/J/JACDOO01_Jack_Doohan/jacdoo01.png.transform/1col/image.png",
        ];

        foreach ($fotos as $apiId => $url) {
            Piloto::where('api_id', $apiId)->update(['foto' => $url]);
        }
    }

    private function seedEscuderias(): void
    {
        $logos = [
            'alpine'       => 'https://media.formula1.com/content/dam/fom-website/teams/2025/alpine-logo.png',
            'aston_martin' => 'https://media.formula1.com/content/dam/fom-website/teams/2025/aston-martin-logo.png',
            'ferrari'      => 'https://media.formula1.com/content/dam/fom-website/teams/2025/ferrari-logo.png',
            'haas'         => 'https://media.formula1.com/content/dam/fom-website/teams/2025/haas-logo.png',
            'mclaren'      => 'https://media.formula1.com/content/dam/fom-website/teams/2025/mclaren-logo.png',
            'mercedes'     => 'https://media.formula1.com/content/dam/fom-website/teams/2025/mercedes-logo.png',
            'rb'           => 'https://media.formula1.com/content/dam/fom-website/teams/2025/racing-bulls-logo.png',
            'red_bull'     => 'https://media.formula1.com/content/dam/fom-website/teams/2025/red-bull-racing-logo.png',
            'sauber'       => 'https://media.formula1.com/content/dam/fom-website/teams/2025/kick-sauber-logo.png',
            'williams'     => 'https://media.formula1.com/content/dam/fom-website/teams/2025/williams-logo.png',
            'audi'         => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/92/Audi-Logo_2016.svg/240px-Audi-Logo_2016.svg.png',
            'cadillac'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/02/Cadillac_logo.svg/240px-Cadillac_logo.svg.png',
        ];

        foreach ($logos as $apiId => $url) {
            Escuderia::where('api_id', $apiId)->update(['logo' => $url]);
        }
    }

    private function seedDirectores(): void
    {
        $fotos = [
            'Christian Horner'   => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/Christian_Horner_2017_Malaysia_2.jpg/220px-Christian_Horner_2017_Malaysia_2.jpg',
            'Frédéric Vasseur'   => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/01/Frederic_Vasseur_2018.jpg/220px-Frederic_Vasseur_2018.jpg',
            'Toto Wolff'         => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1b/Toto_Wolff_2017_Malaysia_2.jpg/220px-Toto_Wolff_2017_Malaysia_2.jpg',
            'Andrea Stella'      => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5b/Andrea_Stella_2023.jpg/220px-Andrea_Stella_2023.jpg',
            'Andy Cowell'        => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/97/Andy_Cowell.jpg/220px-Andy_Cowell.jpg',
            'Oliver Oakes'       => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/01/Oliver_Oakes.jpg/220px-Oliver_Oakes.jpg',
            'James Vowles'       => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/James_Vowles_2023.jpg/220px-James_Vowles_2023.jpg',
            'Laurent Mekies'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/63/Laurent_Mekies_2019.jpg/220px-Laurent_Mekies_2019.jpg',
            'Ayao Komatsu'       => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9c/Ayao_Komatsu_2024.jpg/220px-Ayao_Komatsu_2024.jpg',
            'Mattia Binotto'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Mattia_Binotto_2019.jpg/220px-Mattia_Binotto_2019.jpg',
        ];

        foreach ($fotos as $nombre => $url) {
            DirectorEquipo::where('nombre', $nombre)->update(['foto' => $url]);
        }
    }
}
