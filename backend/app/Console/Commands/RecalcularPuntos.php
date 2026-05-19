<?php

namespace App\Console\Commands;

use App\Models\Carrera;
use App\Models\EquipoFantasy;
use App\Models\MiembroLiga;
use App\Models\PuntosEquipoCarrera;
use App\Models\ResultadoCarrera;
use App\Services\ServicioPuntuacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcularPuntos extends Command
{
    protected $signature   = 'puntos:recalcular';
    protected $description = 'Recalcula puntos_carrera y puntos_velocidad de todos los resultados y rehace los totales de equipos';

    public function handle(ServicioPuntuacion $servicio): int
    {
        // ── 1. Recalcular puntos individuales de cada resultado ───────────────
        $this->info('Paso 1/4 — Recalculando puntos por piloto...');

        $resultados = ResultadoCarrera::all();
        $bar = $this->output->createProgressBar($resultados->count());
        $bar->start();

        // Necesitamos una instancia con las reglas cargadas
        foreach ($resultados as $resultado) {
            $servicio->calcularPuntosPiloto($resultado);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // ── 2. Aplicar BEATS_TEAMMATE por carrera ─────────────────────────────
        $this->info('Paso 2/4 — Aplicando bonus superar compañero...');

        $carrerasPuntuadas = Carrera::where('estado', 'scored')->with('resultados')->orderBy('fecha')->get();
        foreach ($carrerasPuntuadas as $carrera) {
            $servicio->aplicarBeatsTeammate($carrera);
        }

        // ── 3. Resetear totales de equipos y miembros ─────────────────────────
        $this->info('Paso 3/4 — Reseteando totales de equipos y ligas...');

        PuntosEquipoCarrera::query()->delete();
        EquipoFantasy::query()->update(['puntos_totales' => 0]);
        MiembroLiga::query()->update(['puntos_totales' => 0]);

        // ── 4. Recalcular puntos de equipos por carrera puntuada ─────────────
        $this->info('Paso 4/4 — Recalculando puntos de equipos...');

        $carreras = Carrera::where('estado', 'scored')
            ->with('resultados')
            ->orderBy('fecha')
            ->get();

        foreach ($carreras as $carrera) {
            $this->line("  → {$carrera->nombre}");

            $equipos = EquipoFantasy::with(['miembroLiga'])->get();

            DB::transaction(function () use ($servicio, $carrera, $equipos) {
                foreach ($equipos as $equipo) {
                    $servicio->calcularPuntosEquipo($equipo, $carrera);
                }
            });
        }

        $this->info('✓ Recálculo completado.');
        return self::SUCCESS;
    }
}
