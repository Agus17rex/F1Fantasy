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
            'name'                => ['required', 'string', 'max:100'],
            'description'         => ['nullable', 'string', 'max:500'],
            'max_members'         => ['integer', 'min:2', 'max:50'],
            'is_private'          => ['boolean'],
            // Presupuesto en millones (el frontend envía el número, ej: 30 = 30M)
            'presupuesto_inicial' => ['integer', 'min:10000000', 'max:200000000'],
        ]);

        $liga = Liga::create([
            ...$validado,
            'owner_id' => $request->user()->id,
            'code'     => strtoupper(Str::random(8)),
            'season'   => date('Y'),
            'status'   => 'active',
        ]);

        // El creador es automáticamente miembro de la liga
        MiembroLiga::create([
            'league_id' => $liga->id,
            'user_id'   => $request->user()->id,
            'joined_at' => now(),
        ]);

        // Se crea un equipo con el presupuesto inicial de la liga
        EquipoFantasy::create([
            'user_id'          => $request->user()->id,
            'league_id'        => $liga->id,
            'name'             => $request->user()->name . ' Fantasy',
            'remaining_budget' => $liga->presupuesto_inicial,
        ]);

        return response()->json($liga->load('miembros'), 201);
    }

    public function show(Liga $liga): JsonResponse
    {
        $liga->load(['propietario', 'miembros.usuario', 'miembros.equipo']);

        // Clasificación de la liga ordenada por puntos
        $clasificacion = MiembroLiga::where('league_id', $liga->id)
            ->with('usuario')
            ->orderByDesc('total_points')
            ->get()
            ->map(function ($miembro, $posicion) {
                return [
                    'posicion'     => $posicion + 1,
                    'usuario'      => $miembro->usuario,
                    'total_puntos' => $miembro->total_points,
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
            'code' => ['required', 'string', 'size:8'],
        ]);

        $liga = Liga::where('code', strtoupper($request->code))
            ->where('status', 'active')
            ->firstOrFail();

        if ($liga->miembros()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Ya eres miembro de esta liga'], 422);
        }

        if ($liga->miembros()->count() >= $liga->max_members) {
            return response()->json(['message' => 'La liga está llena'], 422);
        }

        MiembroLiga::create([
            'league_id' => $liga->id,
            'user_id'   => $request->user()->id,
            'joined_at' => now(),
        ]);

        // El equipo se crea con el presupuesto inicial que definió el creador de la liga
        EquipoFantasy::create([
            'user_id'          => $request->user()->id,
            'league_id'        => $liga->id,
            'name'             => $request->user()->name . ' Fantasy',
            'remaining_budget' => $liga->presupuesto_inicial,
        ]);

        return response()->json(['message' => 'Te has unido a la liga', 'liga' => $liga]);
    }

    /**
     * Mercado: devuelve pilotos, escuderías y directores con flags de propiedad,
     * robo posible y protección de 7 días.
     */
    public function mercado(Request $request, Liga $liga): JsonResponse
    {
        $userId = $request->user()->id;

        $equipo = EquipoFantasy::where('league_id', $liga->id)
            ->where('user_id', $userId)
            ->with(['pilotos', 'escuderias', 'directores'])
            ->first();

        $pilotoIds    = $equipo?->pilotos->pluck('id')->all() ?? [];
        $escuderiaIds = $equipo?->escuderias->pluck('id')->all() ?? [];
        $directorIds  = $equipo?->directores->pluck('id')->all() ?? [];

        $pilotosConRol = $equipo?->pilotos->mapWithKeys(fn($p) => [$p->id => $p->pivot->role]) ?? collect();

        // ── Obtener todos los pilotos/escuderías/directores poseídos en la liga ──
        // Pilotos en equipos ajenos (misma liga, otro usuario)
        $pilotosAjenos = DB::table('fantasy_team_drivers')
            ->join('fantasy_teams', 'fantasy_teams.id', '=', 'fantasy_team_drivers.fantasy_team_id')
            ->join('users', 'users.id', '=', 'fantasy_teams.user_id')
            ->where('fantasy_teams.league_id', $liga->id)
            ->where('fantasy_teams.user_id', '!=', $userId)
            ->whereNull('fantasy_team_drivers.removed_at')
            ->select(
                'fantasy_team_drivers.driver_id',
                'fantasy_team_drivers.selected_at',
                'users.id as propietario_id',
                'users.name as propietario_name'
            )
            ->get()
            ->keyBy('driver_id');

        // Escuderías en equipos ajenos
        $escuderiasAjenas = DB::table('fantasy_team_constructors')
            ->join('fantasy_teams', 'fantasy_teams.id', '=', 'fantasy_team_constructors.fantasy_team_id')
            ->join('users', 'users.id', '=', 'fantasy_teams.user_id')
            ->where('fantasy_teams.league_id', $liga->id)
            ->where('fantasy_teams.user_id', '!=', $userId)
            ->whereNull('fantasy_team_constructors.removed_at')
            ->select(
                'fantasy_team_constructors.constructor_id',
                'fantasy_team_constructors.selected_at',
                'users.id as propietario_id',
                'users.name as propietario_name'
            )
            ->get()
            ->keyBy('constructor_id');

        // Directores en equipos ajenos
        $directoresAjenos = DB::table('fantasy_team_principals')
            ->join('fantasy_teams', 'fantasy_teams.id', '=', 'fantasy_team_principals.fantasy_team_id')
            ->join('users', 'users.id', '=', 'fantasy_teams.user_id')
            ->where('fantasy_teams.league_id', $liga->id)
            ->where('fantasy_teams.user_id', '!=', $userId)
            ->whereNull('fantasy_team_principals.removed_at')
            ->select(
                'fantasy_team_principals.team_principal_id',
                'fantasy_team_principals.selected_at',
                'users.id as propietario_id',
                'users.name as propietario_name'
            )
            ->get()
            ->keyBy('team_principal_id');

        $ahora = now();

        $pilotos = Piloto::with('escuderia')
            ->where('is_active', true)
            ->where('es_reserva', false)
            ->orderBy('last_name')
            ->get()
            ->map(function ($p) use ($pilotoIds, $pilotosConRol, $pilotosAjenos, $ahora) {
                $ajeno = $pilotosAjenos->get($p->id);
                $enEquipoAjeno = $ajeno !== null;
                $protegido     = false;
                $diasProteccion = 0;
                $propietario   = null;

                if ($enEquipoAjeno && $ajeno->selected_at) {
                    $selectedAt = \Carbon\Carbon::parse($ajeno->selected_at);
                    $diffDias   = (int) $ahora->diffInDays($selectedAt);
                    if ($diffDias < 7) {
                        $protegido      = true;
                        $diasProteccion = 7 - $diffDias;
                    }
                    $propietario = ['id' => $ajeno->propietario_id, 'name' => $ajeno->propietario_name];
                }

                return array_merge($p->toArray(), [
                    'en_equipo'      => in_array($p->id, $pilotoIds),
                    'rol'            => $pilotosConRol[$p->id] ?? null,
                    'en_equipo_ajeno' => $enEquipoAjeno,
                    'propietario'    => $propietario,
                    'protegido'      => $protegido,
                    'dias_proteccion' => $diasProteccion,
                ]);
            });

        $escuderias = Escuderia::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($e) use ($escuderiaIds, $escuderiasAjenas, $ahora) {
                $ajeno = $escuderiasAjenas->get($e->id);
                $enEquipoAjeno = $ajeno !== null;
                $protegido     = false;
                $diasProteccion = 0;
                $propietario   = null;

                if ($enEquipoAjeno && $ajeno->selected_at) {
                    $selectedAt = \Carbon\Carbon::parse($ajeno->selected_at);
                    $diffDias   = (int) $ahora->diffInDays($selectedAt);
                    if ($diffDias < 7) {
                        $protegido      = true;
                        $diasProteccion = 7 - $diffDias;
                    }
                    $propietario = ['id' => $ajeno->propietario_id, 'name' => $ajeno->propietario_name];
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
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($d) use ($directorIds, $directoresAjenos, $ahora) {
                $ajeno = $directoresAjenos->get($d->id);
                $enEquipoAjeno = $ajeno !== null;
                $protegido     = false;
                $diasProteccion = 0;
                $propietario   = null;

                if ($enEquipoAjeno && $ajeno->selected_at) {
                    $selectedAt = \Carbon\Carbon::parse($ajeno->selected_at);
                    $diffDias   = (int) $ahora->diffInDays($selectedAt);
                    if ($diffDias < 7) {
                        $protegido      = true;
                        $diasProteccion = 7 - $diffDias;
                    }
                    $propietario = ['id' => $ajeno->propietario_id, 'name' => $ajeno->propietario_name];
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
            'presupuesto_restante' => $equipo?->remaining_budget ?? $liga->presupuesto_inicial,
            'pilotos'              => $pilotos,
            'escuderias'           => $escuderias,
            'directores'           => $directores,
        ]);
    }

    public function destroy(Request $request, Liga $liga): JsonResponse
    {
        if ($liga->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo el propietario puede eliminar la liga'], 403);
        }

        $liga->delete();

        return response()->json(['message' => 'Liga eliminada']);
    }
}
