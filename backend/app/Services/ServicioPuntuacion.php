<?php

namespace App\Services;

use App\Models\EquipoFantasy;
use App\Models\MiembroLiga;
use App\Models\PuntosEquipoCarrera;
use App\Models\Carrera;
use App\Models\ResultadoCarrera;
use App\Models\ReglaPuntuacion;
use App\Support\Traducciones;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServicioPuntuacion
{
    private Collection $reglas;

    public function __construct()
    {
        $this->reglas = ReglaPuntuacion::where('activa', true)->get()->keyBy('evento');
    }

    /**
     * Calcula los puntos fantasy de un piloto en una carrera
     */
    public function calcularPuntosPiloto(ResultadoCarrera $resultado): int
    {
        $puntos = 0;

        // Posición final en carrera
        $evLlegada = 'FINISH_P' . $resultado->posicion_final;
        if ($resultado->posicion_final && isset($this->reglas[$evLlegada])) {
            $puntos += $this->reglas[$evLlegada]->puntos;
        }

        // Posición en clasificación
        $evQuali = 'QUALI_P' . $resultado->posicion_clasificacion;
        if ($resultado->posicion_clasificacion && isset($this->reglas[$evQuali])) {
            $puntos += $this->reglas[$evQuali]->puntos;
        }

        // Vuelta rápida
        if ($resultado->vuelta_rapida && isset($this->reglas['FASTEST_LAP'])) {
            $puntos += $this->reglas['FASTEST_LAP']->puntos;
        }

        // Piloto del día
        if ($resultado->piloto_del_dia && isset($this->reglas['DRIVER_OF_DAY'])) {
            $puntos += $this->reglas['DRIVER_OF_DAY']->puntos;
        }

        // Penalizaciones
        if (in_array($resultado->estado, Traducciones::STATUS_ABANDONO, true)
            && isset($this->reglas['DNF'])) {
            $puntos += $this->reglas['DNF']->puntos;
        }
        if ($resultado->estado === 'No salió' && isset($this->reglas['DNS'])) {
            $puntos += $this->reglas['DNS']->puntos;
        }
        if ($resultado->estado === 'Descalificado' && isset($this->reglas['DISQUALIFIED'])) {
            $puntos += $this->reglas['DISQUALIFIED']->puntos;
        }

        $resultado->update(['puntos_fantasy' => $puntos, 'puntos_calculados' => true]);

        return $puntos;
    }

    /**
     * Procesa los puntos de todos los equipos para una carrera
     */
    public function procesarPuntosCarrera(Carrera $carrera): void
    {
        // 1. Calcular puntos individuales de cada piloto
        $carrera->resultados->each(fn($r) => $this->calcularPuntosPiloto($r));

        // 2. Calcular puntos de cada equipo fantasy
        $equipos = EquipoFantasy::with(['pilotos', 'escuderias', 'directores', 'miembroLiga'])->get();

        DB::transaction(function () use ($carrera, $equipos) {
            foreach ($equipos as $equipo) {
                $this->calcularPuntosEquipo($equipo, $carrera);
            }

            // 3. Distribuir bonus de presupuesto por liga (catch-up mechanic)
            $equipos->groupBy('liga_id')->each(function (Collection $grupoLiga) {
                $this->distribuirPresupuestoLiga($grupoLiga);
            });

            $carrera->update(['estado' => 'scored']);
        });
    }

    /**
     * Calcula los puntos de un equipo para una carrera
     */
    public function calcularPuntosEquipo(EquipoFantasy $equipo, Carrera $carrera): PuntosEquipoCarrera
    {
        $total    = 0;
        $desglose = ['pilotos' => [], 'escuderias' => [], 'directores' => []];

        // ── Pilotos del equipo ────────────────────────────────────────────────
        foreach ($equipo->pilotos as $piloto) {
            $resultado = ResultadoCarrera::where('carrera_id', $carrera->id)
                ->where('piloto_id', $piloto->id)
                ->first();

            $pts = $resultado?->puntos_fantasy ?? 0;
            $desglose['pilotos'][$piloto->id] = ['total' => $pts];
            $total += $pts;
        }

        // ── Escudería (suma de sus dos pilotos) ───────────────────────────────
        foreach ($equipo->escuderias as $escuderia) {
            $pts = ResultadoCarrera::where('carrera_id', $carrera->id)
                ->where('escuderia_id', $escuderia->id)
                ->sum('puntos_fantasy');

            $desglose['escuderias'][$escuderia->id] = ['total' => $pts];
            $total += $pts;
        }

        // ── Director (puntos = escudería / 2, redondeado) ────────────────────
        foreach ($equipo->directores as $director) {
            if (!$director->escuderia_id) continue;

            $ptsEscuderia = ResultadoCarrera::where('carrera_id', $carrera->id)
                ->where('escuderia_id', $director->escuderia_id)
                ->sum('puntos_fantasy');

            $pts = (int) round($ptsEscuderia / 2);
            $desglose['directores'][$director->id] = ['total' => $pts];
            $total += $pts;
        }

        $registro = PuntosEquipoCarrera::updateOrCreate(
            ['equipo_fantasy_id' => $equipo->id, 'carrera_id' => $carrera->id],
            ['puntos_obtenidos' => $total, 'desglose' => $desglose]
        );

        // Actualizar puntos totales del equipo y del miembro en la liga
        $equipo->increment('puntos_totales', $total);

        MiembroLiga::where('liga_id', $equipo->liga_id)
            ->where('usuario_id', $equipo->usuario_id)
            ->increment('puntos_totales', $total);

        return $registro;
    }

    /**
     * Distribuye bonus de presupuesto a todos los equipos de una liga.
     * Mecánica catch-up: el último recibe más dinero.
     */
    private function distribuirPresupuestoLiga(Collection $equipos): void
    {
        $total = $equipos->count();

        $ordenados = $equipos->sortBy('puntos_totales')->values();

        foreach ($ordenados as $posicionDesdeAbajo => $equipo) {
            $rangoDesdeAbajo = $total - $posicionDesdeAbajo;
            $bonus = $rangoDesdeAbajo * 2_000_000;

            $equipo->increment('presupuesto_restante', $bonus);
        }
    }
}
