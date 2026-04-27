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

    // ─── Directores ───────────────────────────────────────────────────────────

    public function directores(): JsonResponse
    {
        $directores = DirectorEquipo::with('escuderia')
            ->where('activo', true)
            ->orderByDesc('precio')
            ->get();

        return response()->json($directores);
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

        // ── Escuderías: suma de puntos_fantasy de sus pilotos ─────────────────
        $escuderiasTotales = DB::table('resultados_carrera')
            ->where('puntos_calculados', true)
            ->selectRaw('escuderia_id, SUM(puntos_fantasy) as total_pts')
            ->groupBy('escuderia_id')
            ->pluck('total_pts', 'escuderia_id');

        $escuderias = Escuderia::where('activa', true)
            ->get()
            ->map(fn($e) => array_merge($e->toArray(), [
                'total_fantasy_pts' => (int) ($escuderiasTotales[$e->id] ?? 0),
            ]))
            ->sortByDesc('total_fantasy_pts')
            ->values();

        // ── Directores: ROUND(puntos_constructor_por_carrera / 2) sumados ─────
        $ptsPorCarreraYConstructor = DB::table('resultados_carrera')
            ->where('puntos_calculados', true)
            ->selectRaw('escuderia_id, carrera_id, SUM(puntos_fantasy) as constructor_pts')
            ->groupBy('escuderia_id', 'carrera_id')
            ->get()
            ->groupBy('escuderia_id');

        $directores = DirectorEquipo::with('escuderia')
            ->where('activo', true)
            ->get()
            ->map(function ($d) use ($ptsPorCarreraYConstructor) {
                $totalPts = 0;
                if ($d->escuderia_id && isset($ptsPorCarreraYConstructor[$d->escuderia_id])) {
                    foreach ($ptsPorCarreraYConstructor[$d->escuderia_id] as $fila) {
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
        $clasificacion = $this->servicioApi->obtenerClasificacionPilotos();
        return response()->json($clasificacion);
    }

    public function clasificacionEscuderias(): JsonResponse
    {
        $clasificacion = $this->servicioApi->obtenerClasificacionEscuderias();
        return response()->json($clasificacion);
    }
}
