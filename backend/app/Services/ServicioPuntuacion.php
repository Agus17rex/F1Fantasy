<?php

namespace App\Services;

use App\Models\EquipoFantasy;
use App\Models\MiembroLiga;
use App\Models\PuntosEquipoCarrera;
use App\Models\Carrera;
use App\Models\ResultadoCarrera;
use App\Models\ReglaPuntuacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServicioPuntuacion
{
    private Collection $reglas;

    public function __construct()
    {
        $this->reglas = ReglaPuntuacion::where('is_active', true)->get()->keyBy('event');
    }

    /**
     * Calcula los puntos fantasy de un piloto en una carrera
     */
    public function calcularPuntosPiloto(ResultadoCarrera $resultado): int
    {
        $puntos = 0;

        // Posición final en carrera
        $evLlegada = 'FINISH_P' . $resultado->finish_position;
        if ($resultado->finish_position && isset($this->reglas[$evLlegada])) {
            $puntos += $this->reglas[$evLlegada]->points;
        }

        // Posición en clasificación
        $evQuali = 'QUALI_P' . $resultado->qualifying_position;
        if ($resultado->qualifying_position && isset($this->reglas[$evQuali])) {
            $puntos += $this->reglas[$evQuali]->points;
        }

        // Vuelta rápida
        if ($resultado->fastest_lap && isset($this->reglas['FASTEST_LAP'])) {
            $puntos += $this->reglas['FASTEST_LAP']->points;
        }

        // Piloto del día
        if ($resultado->driver_of_the_day && isset($this->reglas['DRIVER_OF_DAY'])) {
            $puntos += $this->reglas['DRIVER_OF_DAY']->points;
        }

        // Penalizaciones
        if (in_array($resultado->status, ['DNF', 'Accident', 'Mechanical', 'Collision'])
            && isset($this->reglas['DNF'])) {
            $puntos += $this->reglas['DNF']->points;
        }
        if ($resultado->status === 'DNS' && isset($this->reglas['DNS'])) {
            $puntos += $this->reglas['DNS']->points;
        }
        if ($resultado->status === 'Disqualified' && isset($this->reglas['DISQUALIFIED'])) {
            $puntos += $this->reglas['DISQUALIFIED']->points;
        }

        $resultado->update(['fantasy_points' => $puntos, 'fantasy_points_calculated' => true]);

        return $puntos;
    }

    /**
     * Procesa los puntos de todos los equipos para una carrera
     */
    public function procesarPuntosCarrera(Carrera $carrera): void
    {
        // 1. Calcular puntos individuales de cada piloto
        $carrera->resultados->each(fn($r) => $this->calcularPuntosPiloto($r));

        // 2. Calcular puntos de cada equipo fantasy (con miembroLiga cargado)
        $equipos = EquipoFantasy::with(['pilotos', 'escuderias', 'directores', 'miembroLiga'])->get();

        DB::transaction(function () use ($carrera, $equipos) {
            foreach ($equipos as $equipo) {
                $this->calcularPuntosEquipo($equipo, $carrera);
            }

            // 3. Distribuir bonus de presupuesto por liga (catch-up mechanic)
            $equipos->groupBy('league_id')->each(function (Collection $grupoLiga) {
                $this->distribuirPresupuestoLiga($grupoLiga);
            });

            $carrera->update(['status' => 'scored']);
        });
    }

    /**
     * Calcula los puntos de un equipo para una carrera
     */
    public function calcularPuntosEquipo(EquipoFantasy $equipo, Carrera $carrera): PuntosEquipoCarrera
    {
        $total    = 0;
        $desglose = ['pilotos' => [], 'escuderias' => [], 'directores' => []];

        // ── Todos los pilotos del equipo puntúan ─────────────────────────────
        foreach ($equipo->pilotos as $piloto) {
            $resultado = ResultadoCarrera::where('race_id', $carrera->id)
                ->where('driver_id', $piloto->id)
                ->first();

            $pts = $resultado?->fantasy_points ?? 0;
            $desglose['pilotos'][$piloto->id] = ['total' => $pts];
            $total += $pts;
        }

        // ── Escudería (suma de sus dos pilotos) ───────────────────────────────
        foreach ($equipo->escuderias as $escuderia) {
            $pts = ResultadoCarrera::where('race_id', $carrera->id)
                ->where('constructor_id', $escuderia->id)
                ->sum('fantasy_points');

            $desglose['escuderias'][$escuderia->id] = ['total' => $pts];
            $total += $pts;
        }

        // ── Director (puntos = escudería / 2, redondeado) ────────────────────
        // El director gana puntos proporcionales al rendimiento de su equipo
        foreach ($equipo->directores as $director) {
            if (!$director->constructor_id) continue;

            $ptsEscuderia = ResultadoCarrera::where('race_id', $carrera->id)
                ->where('constructor_id', $director->constructor_id)
                ->sum('fantasy_points');

            $pts = (int) round($ptsEscuderia / 2);
            $desglose['directores'][$director->id] = ['total' => $pts];
            $total += $pts;
        }

        $registro = PuntosEquipoCarrera::updateOrCreate(
            ['fantasy_team_id' => $equipo->id, 'race_id' => $carrera->id],
            ['points_earned' => $total, 'breakdown' => $desglose]
        );

        // Actualizar puntos totales del equipo y del miembro en la liga
        $equipo->increment('total_points', $total);

        MiembroLiga::where('league_id', $equipo->league_id)
            ->where('user_id', $equipo->user_id)
            ->increment('total_points', $total);

        return $registro;
    }

    /**
     * Distribuye bonus de presupuesto a todos los equipos de una liga.
     * Mecánica catch-up: el último recibe más dinero.
     * Formula: bonus = (equipos_en_liga - posicion_desde_arriba + 1) * 2_000_000
     * Equivalente a: el que va último recibe N*2M, el primero recibe 1*2M.
     */
    private function distribuirPresupuestoLiga(Collection $equipos): void
    {
        $total = $equipos->count();

        // Ordenar por total_points ascendente: el primero en el array es el último en la liga
        $ordenados = $equipos->sortBy('total_points')->values();

        foreach ($ordenados as $posicionDesdeAbajo => $equipo) {
            // posicionDesdeAbajo=0 → último → rango N, posicionDesdeAbajo=N-1 → primero → rango 1
            $rangoDesdeAbajo = $total - $posicionDesdeAbajo;
            $bonus = $rangoDesdeAbajo * 2_000_000;

            $equipo->increment('remaining_budget', $bonus);
        }
    }
}
