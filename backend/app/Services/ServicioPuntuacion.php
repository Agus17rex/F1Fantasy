<?php

namespace App\Services;

use App\Models\Coche;
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
     *  - puntos_carrera → posición final + vuelta rápida + adelantamientos
     *                     + penalizaciones de estado + penalizaciones manuales
     *  - puntos_qualy   → posición en clasificación (QUALI_P1 a QUALI_P10)
     *  - puntos_fantasy = puntos_carrera + puntos_qualy (total individual)
     *
     * NO incluye BEATS_TEAMMATE ni BEATS_TEAMMATE_QUALY
     * (requieren comparar con el compañero → se calculan en post-proceso)
     */
    public function calcularPuntosPiloto(ResultadoCarrera $resultado): int
    {
        $puntosCarrera = 0;
        $puntosQualy   = 0;

        // ── Posición final de la carrera ──────────────────────────────────────
        $evLlegada = 'FINISH_P' . $resultado->posicion_final;
        if ($resultado->posicion_final && isset($this->reglas[$evLlegada])) {
            $puntosCarrera += $this->reglas[$evLlegada]->puntos;
        }

        // ── Vuelta rápida ─────────────────────────────────────────────────────
        if ($resultado->vuelta_rapida && isset($this->reglas['FASTEST_LAP'])) {
            $puntosCarrera += $this->reglas['FASTEST_LAP']->puntos;
        }

        // ── Adelantamientos (posiciones ganadas en carrera) ───────────────────
        if ($resultado->posicion_salida && $resultado->posicion_final) {
            $ganadas = $resultado->posicion_salida - $resultado->posicion_final;
            if ($ganadas >= 5 && isset($this->reglas['OVERTAKES_5'])) {
                $puntosCarrera += $this->reglas['OVERTAKES_5']->puntos;
            } elseif ($ganadas >= 3 && isset($this->reglas['OVERTAKES_3'])) {
                $puntosCarrera += $this->reglas['OVERTAKES_3']->puntos;
            }
        }

        // ── Penalizaciones por estado ─────────────────────────────────────────
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

        // ── Penalizaciones manuales (introducidas por el admin) ───────────────
        if ($resultado->penalizacion_grid && isset($this->reglas['PENALTY_GRID'])) {
            $puntosCarrera += $this->reglas['PENALTY_GRID']->puntos;
        }
        if ($resultado->penalizacion_tiempo && isset($this->reglas['PENALTY_TIME'])) {
            $puntosCarrera += $this->reglas['PENALTY_TIME']->puntos;
        }

        // ── Posición de clasificación (qualy P1-P10) ──────────────────────────
        $evQuali = 'QUALI_P' . $resultado->posicion_clasificacion;
        if ($resultado->posicion_clasificacion && isset($this->reglas[$evQuali])) {
            $puntosQualy += $this->reglas[$evQuali]->puntos;
        }

        $total = $puntosCarrera + $puntosQualy;

        $resultado->update([
            'puntos_carrera'    => $puntosCarrera,
            'puntos_qualy'      => $puntosQualy,
            'puntos_fantasy'    => $total,
            'puntos_calculados' => true,
        ]);

        return $total;
    }

    /**
     * Post-proceso: otorga BEATS_TEAMMATE en carrera y BEATS_TEAMMATE_QUALY en clasificación.
     *
     * Carrera: el piloto con mejor posicion_final gana +3 en puntos_carrera.
     *   - Si solo uno terminó (posicion_final no null), ese gana.
     *   - Si ninguno terminó, no hay bonus.
     *
     * Qualy: el piloto con mejor posicion_clasificacion gana +3 en puntos_qualy.
     *   - Si solo uno tiene posicion_clasificacion, ese gana.
     *   - Si ninguno tiene qualy, no hay bonus.
     */
    public function aplicarBeatsTeammate(Carrera $carrera): void
    {
        // Recargar resultados frescos de la BD (ya tienen los puntos base calculados)
        $resultadosPorEscuderia = ResultadoCarrera::where('carrera_id', $carrera->id)
            ->get()
            ->groupBy('escuderia_id');

        foreach ($resultadosPorEscuderia as $escuderiaId => $resultados) {
            // Solo aplica cuando hay exactamente 2 pilotos de la escudería
            if ($resultados->count() !== 2) continue;

            [$r1, $r2] = [$resultados[0], $resultados[1]];

            // ── Superar compañero en CARRERA → puntos_carrera ────────────────
            if (isset($this->reglas['BEATS_TEAMMATE'])) {
                $bonusCarrera = $this->reglas['BEATS_TEAMMATE']->puntos;
                $ganadorCarrera = null;

                if ($r1->posicion_final !== null && $r2->posicion_final !== null) {
                    $ganadorCarrera = $r1->posicion_final < $r2->posicion_final ? $r1 : $r2;
                } elseif ($r1->posicion_final !== null) {
                    $ganadorCarrera = $r1;
                } elseif ($r2->posicion_final !== null) {
                    $ganadorCarrera = $r2;
                }

                if ($ganadorCarrera) {
                    // Bonus de piloto: va directo a puntos_fantasy, no a puntos_carrera
                    $ganadorCarrera->increment('puntos_fantasy', $bonusCarrera);
                }
            }

            // ── Superar compañero en QUALY → bonus de piloto ─────────────────
            if (isset($this->reglas['BEATS_TEAMMATE_QUALY'])) {
                $bonusQualy = $this->reglas['BEATS_TEAMMATE_QUALY']->puntos;
                $ganadorQualy = null;

                if ($r1->posicion_clasificacion !== null && $r2->posicion_clasificacion !== null) {
                    $ganadorQualy = $r1->posicion_clasificacion < $r2->posicion_clasificacion ? $r1 : $r2;
                } elseif ($r1->posicion_clasificacion !== null) {
                    $ganadorQualy = $r1;
                } elseif ($r2->posicion_clasificacion !== null) {
                    $ganadorQualy = $r2;
                }

                if ($ganadorQualy) {
                    // Bonus de piloto: va directo a puntos_fantasy, no a puntos_qualy
                    $ganadorQualy->increment('puntos_fantasy', $bonusQualy);
                }
            }
        }
    }

    /**
     * Procesa los puntos de todos los equipos para una carrera
     */
    public function procesarPuntosCarrera(Carrera $carrera): void
    {
        // 1. Calcular puntos base de cada piloto (posición + penalizaciones + bonos individuales)
        $carrera->load('resultados');
        $carrera->resultados->each(fn($r) => $this->calcularPuntosPiloto($r));

        // 2. BEATS_TEAMMATE (necesita que todos los pilotos tengan puntos base calculados)
        $this->aplicarBeatsTeammate($carrera);

        // 3. Calcular puntos de cada equipo fantasy
        // Se cargan pilotos/coches/escuderias activos porque es la primera puntuación
        // y calcularPuntosEquipo los necesita para construir el snapshot.
        $equipos = EquipoFantasy::with(['pilotos', 'escuderias', 'coches', 'miembroLiga'])->get();

        DB::transaction(function () use ($carrera, $equipos) {
            foreach ($equipos as $equipo) {
                $this->calcularPuntosEquipo($equipo, $carrera);
            }

            // 4. Distribuir bonus de presupuesto por liga (catch-up)
            $equipos->groupBy('liga_id')->each(function (Collection $grupoLiga) {
                $this->distribuirPresupuestoLiga($grupoLiga);
            });

            $carrera->update(['estado' => 'scored']);
        });
    }

    /**
     * Calcula los puntos de un equipo para una carrera.
     *
     * Estrategia de composición del equipo:
     *  - PRIMERA vez (no existe PuntosEquipoCarrera): usa el equipo ACTIVO en ese momento
     *    (pilotos/coches/escuderias con fecha_baja IS NULL). Guarda el snapshot en desglose.
     *  - RECÁLCULO (ya existe el registro): reutiliza los IDs del desglose guardado.
     *    Esto preserva exactamente qué equipo se usó en la puntuación original.
     *
     *   2 pilotos  → cada uno aporta sus puntos_fantasy completos
     *   1 escudería → SUMA(puntos_carrera de sus 2 pilotos reales) ÷ 2
     *   1 coche     → SUMA(puntos qualy de sus 2 pilotos reales)   ÷ 2
     */
    public function calcularPuntosEquipo(EquipoFantasy $equipo, Carrera $carrera): PuntosEquipoCarrera
    {
        $total    = 0;
        $desglose = ['pilotos' => [], 'escuderias' => [], 'coches' => []];

        // ── Determinar qué IDs usar: snapshot guardado o equipo activo actual ──
        $existente = PuntosEquipoCarrera::where('equipo_fantasy_id', $equipo->id)
            ->where('carrera_id', $carrera->id)
            ->first();

        if ($existente && !empty($existente->desglose)) {
            // RECÁLCULO: usar exactamente los IDs guardados en el snapshot
            $b            = $existente->desglose;
            $pilotoIds    = array_map('intval', array_keys($b['pilotos']    ?? []));
            $escuderiaIds = array_map('intval', array_keys($b['escuderias'] ?? []));
            $cocheIds     = array_map('intval', array_keys($b['coches']     ?? []));
        } else {
            // PRIMERA PUNTUACIÓN: usar el equipo activo en este momento
            $pilotoIds    = $equipo->pilotos->pluck('id')->all();
            $escuderiaIds = $equipo->escuderias->pluck('id')->all();
            $cocheIds     = $equipo->coches->pluck('id')->all();
        }

        // ── Pilotos ───────────────────────────────────────────────────────────
        foreach ($pilotoIds as $pilotoId) {
            $resultado = ResultadoCarrera::where('carrera_id', $carrera->id)
                ->where('piloto_id', $pilotoId)
                ->first();

            $pts = $resultado?->puntos_fantasy ?? 0;
            $desglose['pilotos'][$pilotoId] = ['total' => $pts];
            $total += $pts;
        }

        // ── Escuderías ────────────────────────────────────────────────────────
        foreach ($escuderiaIds as $escuderiaId) {
            $sumaCarrera = ResultadoCarrera::where('carrera_id', $carrera->id)
                ->where('escuderia_id', $escuderiaId)
                ->sum('puntos_carrera');

            $pts = (int) round($sumaCarrera / 2);
            $desglose['escuderias'][$escuderiaId] = ['total' => $pts];
            $total += $pts;
        }

        // ── Coches ────────────────────────────────────────────────────────────
        $coches = Coche::whereIn('id', $cocheIds)->get();
        foreach ($coches as $coche) {
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
        $total    = $equipos->count();
        $ordenados = $equipos->sortBy('puntos_totales')->values();

        foreach ($ordenados as $posicionDesdeAbajo => $equipo) {
            $rangoDesdeAbajo = $total - $posicionDesdeAbajo;
            $bonus = $rangoDesdeAbajo * 2_000_000;
            $equipo->increment('presupuesto_restante', $bonus);
        }
    }
}
