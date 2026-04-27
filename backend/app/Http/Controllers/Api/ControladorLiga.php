<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DirectorEquipo;
use App\Models\Escuderia;
use App\Models\EquipoFantasy;
use App\Models\Liga;
use App\Models\MiembroLiga;
use App\Models\Piloto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ControladorLiga extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $ligas = $request->user()
            ->ligas()
            ->with(['propietario', 'miembros'])
            ->withCount('miembros')
            ->get();

        return response()->json($ligas);
    }

    public function store(Request $request): JsonResponse
    {
        $validado = $request->validate([
            'nombre'              => ['required', 'string', 'max:100'],
            'descripcion'         => ['nullable', 'string', 'max:500'],
            'max_miembros'        => ['integer', 'min:2', 'max:50'],
            'es_privada'          => ['boolean'],
            'presupuesto_inicial' => ['integer', 'min:10000000', 'max:200000000'],
        ]);

        $liga = Liga::create([
            ...$validado,
            'propietario_id' => $request->user()->id,
            'codigo'         => strtoupper(Str::random(8)),
            'temporada'      => date('Y'),
            'estado'         => 'active',
        ]);

        // El creador es automáticamente miembro de la liga
        MiembroLiga::create([
            'liga_id'     => $liga->id,
            'usuario_id'  => $request->user()->id,
            'fecha_union' => now(),
        ]);

        // Se crea un equipo con el presupuesto inicial de la liga
        EquipoFantasy::create([
            'usuario_id'           => $request->user()->id,
            'liga_id'              => $liga->id,
            'nombre'               => $request->user()->nombre . ' Fantasy',
            'presupuesto_restante' => $liga->presupuesto_inicial,
        ]);

        return response()->json($liga->load('miembros'), 201);
    }

    public function show(Liga $liga): JsonResponse
    {
        $liga->load(['propietario', 'miembros.usuario', 'miembros.equipo']);

        $clasificacion = MiembroLiga::where('liga_id', $liga->id)
            ->with('usuario')
            ->orderByDesc('puntos_totales')
            ->get()
            ->map(function ($miembro, $posicion) {
                return [
                    'posicion'     => $posicion + 1,
                    'usuario'      => $miembro->usuario,
                    'total_puntos' => $miembro->puntos_totales,
                ];
            });

        return response()->json([
            'liga'          => $liga,
            'clasificacion' => $clasificacion,
        ]);
    }

    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'size:8'],
        ]);

        $liga = Liga::where('codigo', strtoupper($request->codigo))
            ->where('estado', 'active')
            ->firstOrFail();

        if ($liga->miembros()->where('usuario_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Ya eres miembro de esta liga'], 422);
        }

        if ($liga->miembros()->count() >= $liga->max_miembros) {
            return response()->json(['message' => 'La liga está llena'], 422);
        }

        MiembroLiga::create([
            'liga_id'     => $liga->id,
            'usuario_id'  => $request->user()->id,
            'fecha_union' => now(),
        ]);

        EquipoFantasy::create([
            'usuario_id'           => $request->user()->id,
            'liga_id'              => $liga->id,
            'nombre'               => $request->user()->nombre . ' Fantasy',
            'presupuesto_restante' => $liga->presupuesto_inicial,
        ]);

        return response()->json(['message' => 'Te has unido a la liga', 'liga' => $liga]);
    }

    /**
     * Mercado: devuelve pilotos, escuderías y directores con flags de propiedad,
     * robo posible y protección de 7 días.
     */
    public function mercado(Request $request, Liga $liga): JsonResponse
    {
        $usuarioId = $request->user()->id;

        $equipo = EquipoFantasy::where('liga_id', $liga->id)
            ->where('usuario_id', $usuarioId)
            ->with(['pilotos', 'escuderias', 'directores'])
            ->first();

        $pilotoIds    = $equipo?->pilotos->pluck('id')->all() ?? [];
        $escuderiaIds = $equipo?->escuderias->pluck('id')->all() ?? [];
        $directorIds  = $equipo?->directores->pluck('id')->all() ?? [];

        $pilotosConRol = $equipo?->pilotos->mapWithKeys(fn($p) => [$p->id => $p->pivot->rol]) ?? collect();

        // ── Pilotos en equipos ajenos (misma liga, otro usuario) ──
        $pilotosAjenos = DB::table('equipos_fantasy_pilotos')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_pilotos.equipo_fantasy_id')
            ->join('users', 'users.id', '=', 'equipos_fantasy.usuario_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy.usuario_id', '!=', $usuarioId)
            ->whereNull('equipos_fantasy_pilotos.fecha_baja')
            ->select(
                'equipos_fantasy_pilotos.piloto_id',
                'equipos_fantasy_pilotos.fecha_seleccion',
                'users.id as propietario_id',
                'users.nombre as propietario_nombre'
            )
            ->get()
            ->keyBy('piloto_id');

        $escuderiasAjenas = DB::table('equipos_fantasy_escuderias')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_escuderias.equipo_fantasy_id')
            ->join('users', 'users.id', '=', 'equipos_fantasy.usuario_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy.usuario_id', '!=', $usuarioId)
            ->whereNull('equipos_fantasy_escuderias.fecha_baja')
            ->select(
                'equipos_fantasy_escuderias.escuderia_id',
                'equipos_fantasy_escuderias.fecha_seleccion',
                'users.id as propietario_id',
                'users.nombre as propietario_nombre'
            )
            ->get()
            ->keyBy('escuderia_id');

        $directoresAjenos = DB::table('equipos_fantasy_directores')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_directores.equipo_fantasy_id')
            ->join('users', 'users.id', '=', 'equipos_fantasy.usuario_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy.usuario_id', '!=', $usuarioId)
            ->whereNull('equipos_fantasy_directores.fecha_baja')
            ->select(
                'equipos_fantasy_directores.director_id',
                'equipos_fantasy_directores.fecha_seleccion',
                'users.id as propietario_id',
                'users.nombre as propietario_nombre'
            )
            ->get()
            ->keyBy('director_id');

        $ahora = now();

        $pilotos = Piloto::with('escuderia')
            ->where('activo', true)
            ->where('es_reserva', false)
            ->orderBy('apellido')
            ->get()
            ->map(function ($p) use ($pilotoIds, $pilotosConRol, $pilotosAjenos, $ahora) {
                $ajeno = $pilotosAjenos->get($p->id);
                $enEquipoAjeno = $ajeno !== null;
                $protegido     = false;
                $diasProteccion = 0;
                $propietario   = null;

                if ($enEquipoAjeno && $ajeno->fecha_seleccion) {
                    $fechaSel = \Carbon\Carbon::parse($ajeno->fecha_seleccion);
                    $diffDias = (int) $ahora->diffInDays($fechaSel);
                    if ($diffDias < 7) {
                        $protegido      = true;
                        $diasProteccion = 7 - $diffDias;
                    }
                    $propietario = ['id' => $ajeno->propietario_id, 'nombre' => $ajeno->propietario_nombre];
                }

                return array_merge($p->toArray(), [
                    'en_equipo'       => in_array($p->id, $pilotoIds),
                    'rol'             => $pilotosConRol[$p->id] ?? null,
                    'en_equipo_ajeno' => $enEquipoAjeno,
                    'propietario'     => $propietario,
                    'protegido'       => $protegido,
                    'dias_proteccion' => $diasProteccion,
                ]);
            });

        $escuderias = Escuderia::where('activa', true)
            ->orderBy('nombre')
            ->get()
            ->map(function ($e) use ($escuderiaIds, $escuderiasAjenas, $ahora) {
                $ajeno = $escuderiasAjenas->get($e->id);
                $enEquipoAjeno = $ajeno !== null;
                $protegido     = false;
                $diasProteccion = 0;
                $propietario   = null;

                if ($enEquipoAjeno && $ajeno->fecha_seleccion) {
                    $fechaSel = \Carbon\Carbon::parse($ajeno->fecha_seleccion);
                    $diffDias = (int) $ahora->diffInDays($fechaSel);
                    if ($diffDias < 7) {
                        $protegido      = true;
                        $diasProteccion = 7 - $diffDias;
                    }
                    $propietario = ['id' => $ajeno->propietario_id, 'nombre' => $ajeno->propietario_nombre];
                }

                return array_merge($e->toArray(), [
                    'en_equipo'       => in_array($e->id, $escuderiaIds),
                    'en_equipo_ajeno' => $enEquipoAjeno,
                    'propietario'     => $propietario,
                    'protegido'       => $protegido,
                    'dias_proteccion' => $diasProteccion,
                ]);
            });

        $directores = DirectorEquipo::with('escuderia')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(function ($d) use ($directorIds, $directoresAjenos, $ahora) {
                $ajeno = $directoresAjenos->get($d->id);
                $enEquipoAjeno = $ajeno !== null;
                $protegido     = false;
                $diasProteccion = 0;
                $propietario   = null;

                if ($enEquipoAjeno && $ajeno->fecha_seleccion) {
                    $fechaSel = \Carbon\Carbon::parse($ajeno->fecha_seleccion);
                    $diffDias = (int) $ahora->diffInDays($fechaSel);
                    if ($diffDias < 7) {
                        $protegido      = true;
                        $diasProteccion = 7 - $diffDias;
                    }
                    $propietario = ['id' => $ajeno->propietario_id, 'nombre' => $ajeno->propietario_nombre];
                }

                return array_merge($d->toArray(), [
                    'en_equipo'       => in_array($d->id, $directorIds),
                    'en_equipo_ajeno' => $enEquipoAjeno,
                    'propietario'     => $propietario,
                    'protegido'       => $protegido,
                    'dias_proteccion' => $diasProteccion,
                ]);
            });

        return response()->json([
            'presupuesto_restante' => $equipo?->presupuesto_restante ?? $liga->presupuesto_inicial,
            'pilotos'              => $pilotos,
            'escuderias'           => $escuderias,
            'directores'           => $directores,
        ]);
    }

    public function destroy(Request $request, Liga $liga): JsonResponse
    {
        if ($liga->propietario_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo el propietario puede eliminar la liga'], 403);
        }

        $liga->delete();

        return response()->json(['message' => 'Liga eliminada']);
    }
}
