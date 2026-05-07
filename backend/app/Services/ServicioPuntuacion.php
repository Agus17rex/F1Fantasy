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
     * Calcula los puntos fantasy de un piloto en una carrera, separando:
     *  - puntos_carrera   → posición final + penalizaciones (DNF, DNS, descalif.)
     *  - puntos_velocidad → posición de clasificación + vuelta rápida + piloto del día
     *  - puntos_fantasy   = puntos_carrera + puntos_velocidad (total individual)
     */
    public function calcularPuntosPiloto(ResultadoCarrera $resultado): int
    {
        $puntosCarrera   = 0;
        $puntosVelocidad = 0;

        // ── Posición final de la carrera (puntos de carrera) ─────────────────
        $evLlegada = 'FINISH_P' . $resultado->posicion_final;
        if ($resultado->posicion_final && isset($this->reglas[$evLlegada])) {
            $puntosCarrera += $this->reglas[$evLlegada]->puntos;
        }

        // ── Penalizaciones (cuentan como puntos de carrera) ──────────────────
        if (in_array($resultado->estado, Traducciones::STATUS_ABANDONO, true)
            && isset($this->reglas['DNF'])) {
            $puntosCarrera += $this->reglas['DNF']->puntos;
        }
        if ($resultado->estado === 'No salió' && isset($this->reglas['DNS'])) {
            $puntosCarrera += $this->reglas['DNS']->puntos;
        }
        if ($resultado->estado === 'Descalificado' && isset($this->reglas['DISQUALIFIED'])) {
            $puntosCarrera += $this->reglas['DISQUALIFIED']->puntos;
        }

        // ── Posición de clasificación (puntos de velocidad) ──────────────────
        $evQuali = 'QUALI_P' . $resultado->posicion_clasificacion;
        if ($resultado->posicion_clasificacion && isset($this->reglas[$evQuali])) {
            $puntosVelocidad += $this->reglas[$evQuali]->puntos;
        }

        // ── Vuelta rápida (puntos de velocidad) ──────────────────────────────
        if ($resultado->vuelta_rapida && isset($this->reglas['FASTEST_LAP'])) {
            $puntosVelocidad += $this->reglas['FASTEST_LAP']->puntos;
        }

        // ── Piloto del día (puntos de velocidad) ─────────────────────────────
        if ($resultado->piloto_del_dia && isset($this->reglas['DRIVER_OF_DAY'])) {
            $puntosVelocidad += $this->reglas['DRIVER_OF_DAY']->puntos;
        }

        $total = $puntosCarrera + $puntosVelocidad;

        $resultado->update([
            'puntos_carrera'    => $puntosCarrera,
            'puntos_velocidad'  => $puntosVelocidad,
            'puntos_fantasy'    => $total,
            'puntos_calculados' => true,
        ]);

        return $total;
    }

    /**
     * Procesa los puntos de todos los equipos para una carrera
     */
    public function procesarPuntosCarrera(Carrera $carrera): void
    {
        // 1. Calcular puntos individuales de cada piloto
        $carrera->resultados->each(fn($r) => $this->calcularPuntosPiloto($r));

        // 2. Calcular puntos de cada equipo fantasy
        $equipos = EquipoFantasy::with(['pilotos', 'escuderias', 'coches', 'miembroLiga'])->get();

        DB::transaction(function () use ($carrera, $equipos) {
            foreach ($equipos as $equipo) {
                $this->calcularPuntosEquipo($equipo, $carrera);
            }

            // 3. Distribuir bonus de presupuesto por liga (catch-up)
            $equipos->groupBy('liga_id')->each(function (Collection $grupoLiga) {
                $this->distribuirPresupuestoLiga($grupoLiga);
            });

            $carrera->update(['estado' => 'scored']);
        });
    }

    /**
     * Calcula los puntos de un equipo para una carrera con la nueva mecánica:
     *
     *   2 pilotos titulares  → cada uno aporta sus puntos_fantasy completos
     *   1 escudería          → SUMA(puntos_carrera de sus 2 pilotos reales)   ÷ 2
     *   1 coche              → SUMA(puntos_velocidad de sus 2 pilotos reales) ÷ 2
     */
    public function calcularPuntosEquipo(EquipoFantasy $equipo, Carrera $carrera): PuntosEquipoCarrera
    {
        $total    = 0;
        $desglose = ['pilotos' => [], 'escuderias' => [], 'coches' => []];

        // ── Pilotos del equipo (puntúan completos) ────────────────────────────
        foreach ($equipo->pilotos as $piloto) {
            $resultado = ResultadoCarrera::where('carrera_id', $carrera->id)
                ->where('piloto_id', $piloto->id)
                ->first();

            $pts = $resultado?->puntos_fantasy ?? 0;
            $desglose['pilotos'][$piloto->id] = ['total' => $pts];
            $total += $pts;
        }

        // ── Escudería (resultado del domingo / 2) ────────────────────────────
        foreach ($equipo->escuderias as $escuderia) {
            $sumaCarrera = ResultadoCarrera::where('carrera_id', $carrera->id)
                ->where('escuderia_id', $escuderia->id)
                ->sum('puntos_carrera');

            $pts = (int) round($sumaCarrera / 2);
            $desglose['escuderias'][$escuderia->id] = ['total' => $pts];
            $total += $pts;
        }

        // ── Coche (solo posición de clasificación / 2) ───────────────────────
        foreach ($equipo->coches as $coche) {
            if (!$coche->escuderia_id) continue;

            $resultadosEscuderia = ResultadoCarrera::where('carrera_id', $carrera->id)
                ->where('escuderia_id', $coche->escuderia_id)
                ->get();

            $sumaQuali = 0;
            foreach ($resultadosEscuderia as $r) {
                $evQuali = 'QUALI_P' . $r->posicion_clasificacion;
                if ($r->posicion_clasificacion && isset($this->reglas[$evQuali])) {
                    $sumaQuali += $this->reglas[$evQuali]->puntos;
                }
            }

            $pts = (int) round($sumaQuali / 2);
            $desglose['coches'][$coche->id] = ['total' => $pts];
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
