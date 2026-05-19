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
     *  - puntos_carrera   → posición final + penalizaciones de estado + penalizaciones manuales
     *  - puntos_velocidad → qualy + vuelta rápida + piloto del día + adelantamientos
     *  - puntos_fantasy   = puntos_carrera + puntos_velocidad (total individual)
     *
     * NO incluye BEATS_TEAMMATE (requiere comparar con el compañero → se calcula en post-proceso)
     */
    public function calcularPuntosPiloto(ResultadoCarrera $resultado): int
    {
        $puntosCarrera   = 0;
        $puntosVelocidad = 0;

        // ── Posición final de la carrera ──────────────────────────────────────
        $evLlegada = 'FINISH_P' . $resultado->posicion_final;
        if ($resultado->posicion_final && isset($this->reglas[$evLlegada])) {
            $puntosCarrera += $this->reglas[$evLlegada]->puntos;
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

        // ── Posición de clasificación (qualy) ─────────────────────────────────
        $evQuali = 'QUALI_P' . $resultado->posicion_clasificacion;
        if ($resultado->posicion_clasificacion && isset($this->reglas[$evQuali])) {
            $puntosVelocidad += $this->reglas[$evQuali]->puntos;
        }

        // ── Vuelta rápida ─────────────────────────────────────────────────────
        if ($resultado->vuelta_rapida && isset($this->reglas['FASTEST_LAP'])) {
            $puntosVelocidad += $this->reglas['FASTEST_LAP']->puntos;
        }

        // ── Piloto del día ────────────────────────────────────────────────────
        if ($resultado->piloto_del_dia && isset($this->reglas['DRIVER_OF_DAY'])) {
            $puntosVelocidad += $this->reglas['DRIVER_OF_DAY']->puntos;
        }

        // ── Adelantamientos (posiciones ganadas en carrera) ───────────────────
        // Se usan posicion_salida y posicion_final. Si ambas existen y se ganaron
        // posiciones, se aplica el bonus correspondiente (son mutuamente exclusivos:
        // 5+ posiciones da el bonus mayor; 3-4 posiciones da el menor).
        if ($resultado->posicion_salida && $resultado->posicion_final) {
            $ganadas = $resultado->posicion_salida - $resultado->posicion_final;
            if ($ganadas >= 5 && isset($this->reglas['OVERTAKES_5'])) {
                $puntosVelocidad += $this->reglas['OVERTAKES_5']->puntos;
            } elseif ($ganadas >= 3 && isset($this->reglas['OVERTAKES_3'])) {
                $puntosVelocidad += $this->reglas['OVERTAKES_3']->puntos;
            }
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
     * Post-proceso: otorga +BEATS_TEAMMATE al piloto que supera a su compañero en carrera.
     * Reglas:
     *  - Ambos deben tener posicion_final (si uno abandonó sin llegar al final, su posicion_final
     *    puede estar registrada o no según la API).
     *  - El piloto con posicion_final más baja (mejor posición) gana.
     *  - Si sólo uno tiene posicion_final, el que terminó gana.
     *  - Si ninguno tiene posicion_final, no se otorga el bonus.
     */
    public function aplicarBeatsTeammate(Carrera $carrera): void
    {
        if (!isset($this->reglas['BEATS_TEAMMATE'])) return;

        $bonus = $this->reglas['BEATS_TEAMMATE']->puntos;

        // Recargar resultados frescos de la BD (ya tienen los puntos base calculados)
        $resultadosPorEscuderia = ResultadoCarrera::where('carrera_id', $carrera->id)
            ->get()
            ->groupBy('escuderia_id');

        foreach ($resultadosPorEscuderia as $escuderiaId => $resultados) {
            // Solo aplica cuando hay exactamente 2 pilotos de la escudería
            if ($resultados->count() !== 2) continue;

            /** @var ResultadoCarrera $r1 */
            /** @var ResultadoCarrera $r2 */
            [$r1, $r2] = [$resultados[0], $resultados[1]];

            $ganador = null;
            if ($r1->posicion_final !== null && $r2->posicion_final !== null) {
                $ganador = $r1->posicion_final < $r2->posicion_final ? $r1 : $r2;
            } elseif ($r1->posicion_final !== null) {
                $ganador = $r1; // r1 terminó, r2 no
            } elseif ($r2->posicion_final !== null) {
                $ganador = $r2; // r2 terminó, r1 no
            }
            // Si ninguno tiene posicion_final → no hay ganador

            if ($ganador) {
                $ganador->increment('puntos_velocidad', $bonus);
                $ganador->increment('puntos_fantasy',   $bonus);
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
