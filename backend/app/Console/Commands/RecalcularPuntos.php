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
        $this->info('Paso 1/3 — Recalculando puntos por piloto...');

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

        // ── 2. Resetear totales de equipos y miembros ─────────────────────────
        $this->info('Paso 2/3 — Reseteando totales de equipos y ligas...');

        PuntosEquipoCarrera::query()->delete();
        EquipoFantasy::query()->update(['puntos_totales' => 0]);
        MiembroLiga::query()->update(['puntos_totales' => 0]);

        // ── 3. Recalcular puntos de equipos por carrera puntuada ─────────────
        $this->info('Paso 3/3 — Recalculando puntos de equipos...');

        $carreras = Carrera::where('estado', 'scored')
            ->with('resultados')
            ->orderBy('fecha')
            ->get();

        foreach ($carreras as $carrera) {
            $this->line("  → {$carrera->nombre}");

            $equipos = EquipoFantasy::with(['pilotos', 'escuderias', 'coches', 'miembroLiga'])->get();

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
