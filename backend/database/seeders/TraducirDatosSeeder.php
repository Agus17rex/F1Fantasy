<?php

namespace Database\Seeders;

use App\Models\DirectorEquipo;
use App\Models\Escuderia;
use App\Models\Piloto;
use App\Models\ResultadoCarrera;
use App\Support\Traducciones;
use Illuminate\Database\Seeder;

/**
 * Traduce a español los datos que ya estaban guardados en la base de datos
 * en inglés (nacionalidades de pilotos/escuderías/directores y status de
 * resultados de carrera).
 *
 * Es idempotente: si un valor ya está en español se queda igual.
 */
class TraducirDatosSeeder extends Seeder
{
    public function run(): void
    {
        $this->traducirNacionalidades();
        $this->traducirStatusResultados();
    }

    private function traducirNacionalidades(): void
    {
        // Pilotos
        Piloto::query()
            ->whereIn('nacionalidad', array_keys(Traducciones::NACIONALIDADES))
            ->get()
            ->each(function ($p) {
                $p->update(['nacionalidad' => Traducciones::nacionalidad($p->nacionalidad)]);
            });

        // Escuderías
        Escuderia::query()
            ->whereIn('nacionalidad', array_keys(Traducciones::NACIONALIDADES))
            ->get()
            ->each(function ($e) {
                $e->update(['nacionalidad' => Traducciones::nacionalidad($e->nacionalidad)]);
            });

        // Directores
        DirectorEquipo::query()
            ->whereIn('nacionalidad', array_keys(Traducciones::NACIONALIDADES))
            ->get()
            ->each(function ($d) {
                $d->update(['nacionalidad' => Traducciones::nacionalidad($d->nacionalidad)]);
            });
    }

    private function traducirStatusResultados(): void
    {
        ResultadoCarrera::query()
            ->whereIn('estado', array_keys(Traducciones::STATUS_RESULTADO))
            ->get()
            ->each(function ($r) {
                $r->update(['estado' => Traducciones::statusResultado($r->estado)]);
            });
    }
}
