<?php

namespace Database\Seeders;

use App\Models\Coche;
use App\Models\Escuderia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Elimina Sauber de la base de datos.
 * Sauber evolucionó a Audi (sucesor) y Cadillac es equipo nuevo.
 */
class EliminarSauberSeeder extends Seeder
{
    public function run(): void
    {
        $sauber = Escuderia::where('api_id', 'sauber')->first();

        if (!$sauber) {
            $this->command->info('Sauber no encontrado en la BD, nada que eliminar.');
            return;
        }

        // Eliminar el coche de Sauber
        Coche::where('escuderia_id', $sauber->id)->delete();

        // Desvincular pilotos que pudieran apuntar a Sauber
        DB::table('pilotos')
            ->where('escuderia_id', $sauber->id)
            ->update(['escuderia_id' => null, 'activo' => false]);

        // Eliminar la escudería
        $sauber->delete();

        $this->command->info('Sauber eliminado correctamente.');
    }
}
