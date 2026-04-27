<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DirectorEquipo;
use App\Models\Escuderia;
use App\Models\Piloto;
use App\Models\Carrera;
use App\Services\ServicioApiF1;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ControladorF1 extends Controller
{
    public function __construct(private ServicioApiF1 $servicioApi) {}

    // ─── Pilotos ──────────────────────────────────────────────────────────────

    public function pilotos(): JsonResponse
    {
        // Si la base de datos no tiene pilotos, los sincronizamos desde la API
        if (Piloto::count() === 0) {
            $this->servicioApi->sincronizarEscuderias();
            $this->servicioApi->sincronizarPilotos();
        }

        $pilotos = Piloto::with('escuderia')
            ->where('is_active', true)
            ->where('es_reserva', false)
            ->orderBy('last_name')
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
        // Si la base de datos no tiene escuderías, las sincronizamos desde la API
        if (Escuderia::count() === 0) {
            $this->servicioApi->sincronizarEscuderias();
        }

        $escuderias = Escuderia::where('is_active', true)
            ->withCount('pilotos')
            ->orderBy('name')
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

        // Si no hay carreras de la temporada actual, las sincronizamos desde la API
        if (Carrera::where('season', $temporada)->count() === 0) {
            $this->servicioApi->sincronizarCarreras($temporada);
        }

        $carreras = Carrera::with('circuito')
            ->where('season', $temporada)
            ->orderBy('date')
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

        // Si no hay carreras de la temporada actual, sincronizamos primero
        if (Carrera::where('season', $temporada)->count() === 0) {
            $this->servicioApi->sincronizarCarreras($temporada);
        }

        $carrera = Carrera::with('circuito')
            ->where('season', $temporada)
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'upcoming')
            ->orderBy('date')
            ->first();

        return response()->json($carrera);
    }

    // ─── Directores ───────────────────────────────────────────────────────────

    public function directores(): JsonResponse
    {
        $directores = DirectorEquipo::with('escuderia')
            ->where('is_active', true)
            ->orderByDesc('price')
            ->get();

        return response()->json($directores);
    }

    // ─── Ranking Fantasy (puntos acumulados en la temporada) ──────────────────

    public function rankingFantasy(): JsonResponse
    {
        // ── Pilotos: suma de fantasy_points por piloto ────────────────────────
        $pilotosTotales = DB::table('race_results')
            ->where('fantasy_points_calculated', true)
            ->selectRaw('driver_id, SUM(fantasy_points) as total_pts')
            ->groupBy('driver_id')
            ->pluck('total_pts', 'driver_id');

        $pilotos = Piloto::with('escuderia')
            ->where('is_active', true)
            ->where('es_reserva', false)
            ->get()
            ->map(fn($p) => array_merge($p->toArray(), [
                'total_fantasy_pts' => (int) ($pilotosTotales[$p->id] ?? 0),
            ]))
            ->sortByDesc('total_fantasy_pts')
            ->values();

        // ── Escuderías: suma de fantasy_points de sus pilotos ─────────────────
        $escuderiasTotales = DB::table('race_results')
            ->where('fantasy_points_calculated', true)
            ->selectRaw('constructor_id, SUM(fantasy_points) as total_pts')
            ->groupBy('constructor_id')
            ->pluck('total_pts', 'constructor_id');

        $escuderias = Escuderia::where('is_active', true)
            ->get()
            ->map(fn($e) => array_merge($e->toArray(), [
                'total_fantasy_pts' => (int) ($escuderiasTotales[$e->id] ?? 0),
            ]))
            ->sortByDesc('total_fantasy_pts')
            ->values();

        // ── Directores: ROUND(puntos_constructor_por_carrera / 2) sumados ─────
        // Subquery: puntos por (constructor, carrera)
        $ptsPorCarreraYConstructor = DB::table('race_results')
            ->where('fantasy_points_calculated', true)
            ->selectRaw('constructor_id, race_id, SUM(fantasy_points) as constructor_pts')
            ->groupBy('constructor_id', 'race_id')
            ->get()
            ->groupBy('constructor_id');

        $directores = DirectorEquipo::with('escuderia')
            ->where('is_active', true)
            ->get()
            ->map(function ($d) use ($ptsPorCarreraYConstructor) {
                $totalPts = 0;
                if ($d->constructor_id && isset($ptsPorCarreraYConstructor[$d->constructor_id])) {
                    foreach ($ptsPorCarreraYConstructor[$d->constructor_id] as $fila) {
                        $totalPts += (int) round($fila->constructor_pts / 2);
                    }
                }
                return array_merge($d->toArray(), ['total_fantasy_pts' => $totalPts]);
            })
            ->sortByDesc('total_fantasy_pts')
            ->values();

        return response()->json([
            'pilotos'    => $pilotos,
            'escuderias' => $escuderias,
            'directores' => $directores,
        ]);
    }

    // ─── Clasificaciones ──────────────────────────────────────────────────────

    public function clasificacionPilotos(): JsonResponse
    {
        // La clasificación siempre se obtiene en tiempo real desde la API
        $clasificacion = $this->servicioApi->obtenerClasificacionPilotos();
        return response()->json($clasificacion);
    }

    public function clasificacionEscuderias(): JsonResponse
    {
        // La clasificación siempre se obtiene en tiempo real desde la API
        $clasificacion = $this->servicioApi->obtenerClasificacionEscuderias();
        return response()->json($clasificacion);
    }
}
