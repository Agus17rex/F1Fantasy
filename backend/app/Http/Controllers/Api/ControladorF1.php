<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coche;
use App\Models\Escuderia;
use App\Models\Piloto;
use App\Models\Carrera;
use App\Models\ReglaPuntuacion;
use App\Models\ResultadoCarrera;
use App\Services\ServicioApiF1;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ControladorF1 extends Controller
{
    public function __construct(private ServicioApiF1 $servicioApi) {}

    // ─── Pilotos ──────────────────────────────────────────────────────────────

    public function pilotos(): JsonResponse
    {
        if (Piloto::count() === 0) {
            $this->servicioApi->sincronizarEscuderias();
            $this->servicioApi->sincronizarPilotos();
        }

        $pilotos = Piloto::with('escuderia')
            ->where('activo', true)
            ->where('es_reserva', false)
            ->orderBy('apellido')
            ->get();

        return response()->json($pilotos);
    }

    public function piloto(Piloto $piloto): JsonResponse
    {
        $piloto->load('escuderia');
        return response()->json($piloto);
    }

    // ─── Escuderías ───────────────────────────────────────────────────────────

    public function escuderias(): JsonResponse
    {
        if (Escuderia::count() === 0) {
            $this->servicioApi->sincronizarEscuderias();
        }

        $escuderias = Escuderia::where('activa', true)
            ->withCount('pilotos')
            ->orderBy('nombre')
            ->get();

        return response()->json($escuderias);
    }

    public function escuderia(Escuderia $escuderia): JsonResponse
    {
        $escuderia->load('pilotos');
        return response()->json($escuderia);
    }

    // ─── Carreras / Calendario ────────────────────────────────────────────────

    public function carreras(): JsonResponse
    {
        $temporada = now()->year;

        if (Carrera::where('temporada', $temporada)->count() === 0) {
            $this->servicioApi->sincronizarCarreras($temporada);
        }

        $carreras = Carrera::with('circuito')
            ->where('temporada', $temporada)
            ->orderBy('fecha')
            ->get();

        return response()->json($carreras);
    }

    public function carrera(Carrera $carrera): JsonResponse
    {
        $carrera->load(['circuito', 'resultados.piloto', 'resultados.escuderia']);
        return response()->json($carrera);
    }

    public function proximaCarrera(): JsonResponse
    {
        $temporada = now()->year;

        if (Carrera::where('temporada', $temporada)->count() === 0) {
            $this->servicioApi->sincronizarCarreras($temporada);
        }

        $carrera = Carrera::with('circuito')
            ->where('temporada', $temporada)
            ->where('fecha', '>=', now()->toDateString())
            ->where('estado', 'upcoming')
            ->orderBy('fecha')
            ->first();

        return response()->json($carrera);
    }

    // ─── Reglas de puntuación (público) ──────────────────────────────────────

    public function reglas(): JsonResponse
    {
        $reglas = ReglaPuntuacion::where('activa', true)
            ->orderBy('evento')
            ->get(['evento', 'puntos', 'descripcion']);

        return response()->json($reglas);
    }

    // ─── Coches ───────────────────────────────────────────────────────────────

    public function coches(): JsonResponse
    {
        $coches = Coche::with('escuderia')
            ->where('activo', true)
            ->orderByDesc('precio')
            ->get();

        return response()->json($coches);
    }

    // ─── Ranking Fantasy (puntos acumulados en la temporada) ──────────────────

    public function rankingFantasy(): JsonResponse
    {
        // ── Pilotos: suma de puntos_fantasy por piloto ────────────────────────
        $pilotosTotales = DB::table('resultados_carrera')
            ->where('puntos_calculados', true)
            ->selectRaw('piloto_id, SUM(puntos_fantasy) as total_pts')
            ->groupBy('piloto_id')
            ->pluck('total_pts', 'piloto_id');

        $pilotos = Piloto::with('escuderia')
            ->where('activo', true)
            ->where('es_reserva', false)
            ->get()
            ->map(fn($p) => array_merge($p->toArray(), [
                'total_fantasy_pts' => (int) ($pilotosTotales[$p->id] ?? 0),
            ]))
            ->sortByDesc('total_fantasy_pts')
            ->values();

        // ── Escuderías: ROUND(puntos_carrera / 2) por carrera, sumado ────────
        $carreraPorCarreraYConstructor = DB::table('resultados_carrera')
            ->where('puntos_calculados', true)
            ->selectRaw('escuderia_id, carrera_id, SUM(puntos_carrera) as carrera_pts')
            ->groupBy('escuderia_id', 'carrera_id')
            ->get()
            ->groupBy('escuderia_id');

        $escuderias = Escuderia::where('activa', true)
            ->get()
            ->map(function ($e) use ($carreraPorCarreraYConstructor) {
                $totalPts = 0;
                if (isset($carreraPorCarreraYConstructor[$e->id])) {
                    foreach ($carreraPorCarreraYConstructor[$e->id] as $fila) {
                        $totalPts += (int) round($fila->carrera_pts / 2);
                    }
                }
                return array_merge($e->toArray(), ['total_fantasy_pts' => $totalPts]);
            })
            ->sortByDesc('total_fantasy_pts')
            ->values();

        // ── Coches: ROUND(qualy_pts del constructor por carrera / 2) ─────────
        // Igual que ServicioPuntuacion::calcularPuntosEquipo(): solo puntos de
        // clasificación (QUALI_Pn), no todos los puntos_velocidad.
        $reglasQualy = ReglaPuntuacion::where('activa', true)
            ->where('evento', 'like', 'QUALI_%')
            ->get()
            ->keyBy('evento');

        $resultadosParaCoches = DB::table('resultados_carrera')
            ->where('puntos_calculados', true)
            ->select('escuderia_id', 'carrera_id', 'posicion_clasificacion')
            ->get();

        // Acumular puntos de qualy por (escuderia_id, carrera_id)
        $qualiPorEscuderia = [];
        foreach ($resultadosParaCoches as $r) {
            $evQuali  = 'QUALI_P' . $r->posicion_clasificacion;
            $ptsQuali = ($r->posicion_clasificacion && isset($reglasQualy[$evQuali]))
                ? $reglasQualy[$evQuali]->puntos : 0;

            $qualiPorEscuderia[$r->escuderia_id][$r->escuderia_id . '_' . $r->carrera_id]
                = ($qualiPorEscuderia[$r->escuderia_id][$r->escuderia_id . '_' . $r->carrera_id] ?? 0) + $ptsQuali;
        }

        $coches = Coche::with('escuderia')
            ->where('activo', true)
            ->get()
            ->map(function ($c) use ($qualiPorEscuderia) {
                $totalPts = 0;
                if ($c->escuderia_id && isset($qualiPorEscuderia[$c->escuderia_id])) {
                    foreach ($qualiPorEscuderia[$c->escuderia_id] as $sumaQuali) {
                        $totalPts += (int) round($sumaQuali / 2);
                    }
                }
                return array_merge($c->toArray(), ['total_fantasy_pts' => $totalPts]);
            })
            ->sortByDesc('total_fantasy_pts')
            ->values();

        return response()->json([
            'pilotos'    => $pilotos,
            'escuderias' => $escuderias,
            'coches'     => $coches,
        ]);
    }

    // ─── Puntuaciones por carrera (usuario) ──────────────────────────────────

    /**
     * Misma información que el admin pero accesible a cualquier usuario autenticado.
     */
    public function puntuacionCarrera(Carrera $carrera): JsonResponse
    {
        $resultados = $carrera->resultados()
            ->with(['piloto', 'escuderia'])
            ->orderBy('posicion_final')
            ->get();

        $filas = $resultados->map(fn($r) => [
            'id'                     => $r->id,
            'piloto'                 => [
                'nombre'    => $r->piloto?->nombre,
                'apellido'  => $r->piloto?->apellido,
                'codigo'    => $r->piloto?->codigo,
                'foto'      => $r->piloto?->foto,
                'escuderia' => ['color' => $r->escuderia?->color],
            ],
            'escuderia'              => [
                'nombre' => $r->escuderia?->nombre,
                'color'  => $r->escuderia?->color,
                'logo'   => $r->escuderia?->logo,
            ],
            'posicion_salida'        => $r->posicion_salida,
            'posicion_final'         => $r->posicion_final,
            'posicion_clasificacion' => $r->posicion_clasificacion,
            'estado'                 => $r->estado,
            'vuelta_rapida'          => $r->vuelta_rapida,
            'puntos_carrera'         => $r->puntos_carrera,
            'puntos_qualy'           => $r->puntos_qualy,
            'puntos_fantasy'         => $r->puntos_fantasy,
        ]);

        $porEscuderia = $resultados->groupBy('escuderia_id')->map(function ($grupo) {
            $sumaCarrera = $grupo->sum('puntos_carrera');
            $sumaQualy   = $grupo->sum('puntos_qualy');
            $esc         = $grupo->first()->escuderia;
            $coche       = $esc ? Coche::where('escuderia_id', $esc->id)->where('activo', true)->first() : null;

            return [
                'escuderia'     => ['nombre' => $esc?->nombre, 'color' => $esc?->color, 'logo' => $esc?->logo],
                'coche'         => $coche ? ['nombre' => $coche->nombre, 'foto' => $coche->foto, 'escuderia' => ['color' => $esc?->color]] : null,
                'suma_carrera'  => $sumaCarrera,
                'suma_qualy'    => $sumaQualy,
                'pts_escuderia' => (int) round($sumaCarrera / 2),
                'pts_coche'     => (int) round($sumaQualy / 2),
            ];
        })->sortByDesc('pts_escuderia')->values();

        return response()->json([
            'carrera'       => $carrera->nombre,
            'ronda'         => $carrera->ronda,
            'estado'        => $carrera->estado,
            'resultados'    => $filas,
            'por_escuderia' => $porEscuderia,
        ]);
    }

    /**
     * Totales acumulados de toda la temporada (equivalente a R0).
     */
    public function puntuacionTemporada(): JsonResponse
    {
        $resultados = ResultadoCarrera::where('puntos_calculados', true)
            ->with(['piloto', 'escuderia'])
            ->get();

        // ── Pilotos: suma acumulada ───────────────────────────────────────────
        $filas = $resultados->groupBy('piloto_id')->map(function ($grupo) {
            $r = $grupo->first();
            return [
                'id'                     => $r->id,
                'piloto'                 => [
                    'nombre'    => $r->piloto?->nombre,
                    'apellido'  => $r->piloto?->apellido,
                    'codigo'    => $r->piloto?->codigo,
                    'foto'      => $r->piloto?->foto,
                    'escuderia' => ['color' => $r->escuderia?->color],
                ],
                'escuderia'              => [
                    'nombre' => $r->escuderia?->nombre,
                    'color'  => $r->escuderia?->color,
                    'logo'   => $r->escuderia?->logo,
                ],
                'posicion_salida'        => null,
                'posicion_final'         => null,
                'posicion_clasificacion' => null,
                'estado'                 => null,
                'vuelta_rapida'          => false,
                'puntos_carrera'         => $grupo->sum('puntos_carrera'),
                'puntos_qualy'           => $grupo->sum('puntos_qualy'),
                'puntos_fantasy'         => $grupo->sum('puntos_fantasy'),
            ];
        })->values();

        // ── Por escudería: sumar round(x/2) por carrera, no en global ────────
        $porEscuderia = $resultados->groupBy('escuderia_id')->map(function ($grupoEsc) {
            $esc  = $grupoEsc->first()->escuderia;
            $coche = $esc ? Coche::where('escuderia_id', $esc->id)->where('activo', true)->first() : null;

            $ptsEscuderia = 0;
            $ptsCoche     = 0;
            $sumaCarreraTotal = 0;
            $sumaQualyTotal   = 0;

            foreach ($grupoEsc->groupBy('carrera_id') as $grupoCarrera) {
                $sc = $grupoCarrera->sum('puntos_carrera');
                $sq = $grupoCarrera->sum('puntos_qualy');
                $ptsEscuderia    += (int) round($sc / 2);
                $ptsCoche        += (int) round($sq / 2);
                $sumaCarreraTotal += $sc;
                $sumaQualyTotal   += $sq;
            }

            return [
                'escuderia'     => ['nombre' => $esc?->nombre, 'color' => $esc?->color, 'logo' => $esc?->logo],
                'coche'         => $coche ? ['nombre' => $coche->nombre, 'foto' => $coche->foto, 'escuderia' => ['color' => $esc?->color]] : null,
                'suma_carrera'  => $sumaCarreraTotal,
                'suma_qualy'    => $sumaQualyTotal,
                'pts_escuderia' => $ptsEscuderia,
                'pts_coche'     => $ptsCoche,
            ];
        })->sortByDesc('pts_escuderia')->values();

        return response()->json([
            'carrera'       => 'Total temporada',
            'ronda'         => 0,
            'estado'        => 'season',
            'resultados'    => $filas,
            'por_escuderia' => $porEscuderia,
        ]);
    }

    // ─── Clasificaciones ──────────────────────────────────────────────────────

    public function clasificacionPilotos(): JsonResponse
    {
        $clasificacion = $this->servicioApi->obtenerClasificacionPilotos();
        return response()->json($clasificacion);
    }

    public function clasificacionEscuderias(): JsonResponse
    {
        $clasificacion = $this->servicioApi->obtenerClasificacionEscuderias();
        return response()->json($clasificacion);
    }
}
