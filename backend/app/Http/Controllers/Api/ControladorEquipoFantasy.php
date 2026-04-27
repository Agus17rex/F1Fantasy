<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DirectorEquipo;
use App\Models\Escuderia;
use App\Models\EquipoFantasy;
use App\Models\Liga;
use App\Models\Piloto;
use App\Models\PuntosEquipoCarrera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControladorEquipoFantasy extends Controller
{
    private const MAX_PILOTOS   = 3;
    private const MAX_DIRECTOR  = 1;
    private const MAX_ESCUDERIA = 1;

    // ─── Ver equipo ───────────────────────────────────────────────────────────

    public function show(Request $request, Liga $liga): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        return response()->json([
            'equipo'               => $equipo,
            'es_valido'            => $equipo->esValido(),
            'presupuesto_inicial'  => $liga->presupuesto_inicial,
            'presupuesto_restante' => $equipo->remaining_budget,
        ]);
    }

    /**
     * Ver el equipo de cualquier miembro de la liga (solo lectura)
     */
    public function showDeUsuario(Request $request, Liga $liga, int $userId): JsonResponse
    {
        // Solo miembros de la liga pueden ver equipos ajenos
        $esMiembro = $liga->miembros()->where('user_id', $request->user()->id)->exists();
        if (!$esMiembro) {
            return response()->json(['message' => 'No eres miembro de esta liga'], 403);
        }

        $equipo = EquipoFantasy::where('league_id', $liga->id)
            ->where('user_id', $userId)
            ->with(['pilotos.escuderia', 'escuderias', 'directores.escuderia', 'usuario'])
            ->firstOrFail();

        return response()->json([
            'equipo'    => $equipo,
            'es_valido' => $equipo->esValido(),
        ]);
    }

    /**
     * Desglose de puntos por carrera del equipo del usuario
     */
    public function puntuaciones(Request $request, Liga $liga): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        $registros = PuntosEquipoCarrera::where('fantasy_team_id', $equipo->id)
            ->with('carrera')
            ->orderByDesc('race_id')
            ->get();

        // Pre-cargar nombres de entidades referenciadas en el breakdown
        $pilotoIds    = [];
        $escuderiaIds = [];
        $directorIds  = [];

        foreach ($registros as $reg) {
            $b = $reg->breakdown ?? [];
            $pilotoIds    = array_merge($pilotoIds,    array_keys($b['pilotos']    ?? []));
            $escuderiaIds = array_merge($escuderiaIds, array_keys($b['escuderias'] ?? []));
            $directorIds  = array_merge($directorIds,  array_keys($b['directores'] ?? []));
        }

        $pilotos    = Piloto::whereIn('id', array_unique($pilotoIds))->with('escuderia')->get()->keyBy('id');
        $escuderias = Escuderia::whereIn('id', array_unique($escuderiaIds))->get()->keyBy('id');
        $directores = DirectorEquipo::whereIn('id', array_unique($directorIds))->with('escuderia')->get()->keyBy('id');

        $puntuaciones = $registros->map(function ($reg) use ($pilotos, $escuderias, $directores) {
            $b = $reg->breakdown ?? [];

            $desglosePilotos = collect($b['pilotos'] ?? [])->map(function ($datos, $id) use ($pilotos) {
                $p = $pilotos[(int) $id] ?? null;
                return [
                    'id'     => (int) $id,
                    'nombre' => $p ? "{$p->first_name} {$p->last_name}" : "Piloto #{$id}",
                    'color'  => $p?->escuderia?->color,
                    'total'  => $datos['total'],
                ];
            })->values();

            $desgloseEscuderias = collect($b['escuderias'] ?? [])->map(function ($datos, $id) use ($escuderias) {
                $e = $escuderias[(int) $id] ?? null;
                return [
                    'id'     => (int) $id,
                    'nombre' => $e?->name ?? "Escudería #{$id}",
                    'color'  => $e?->color,
                    'total'  => $datos['total'],
                ];
            })->values();

            $desgloseDirectores = collect($b['directores'] ?? [])->map(function ($datos, $id) use ($directores) {
                $d = $directores[(int) $id] ?? null;
                return [
                    'id'       => (int) $id,
                    'nombre'   => $d?->name ?? "Director #{$id}",
                    'escuderia' => $d?->escuderia?->name,
                    'total'    => $datos['total'],
                ];
            })->values();

            return [
                'carrera_id'    => $reg->race_id,
                'carrera_nombre' => $reg->carrera?->name ?? 'Carrera',
                'carrera_fecha'  => $reg->carrera?->date,
                'puntos_total'   => $reg->points_earned,
                'desglose'       => [
                    'pilotos'    => $desglosePilotos,
                    'escuderias' => $desgloseEscuderias,
                    'directores' => $desgloseDirectores,
                ],
            ];
        });

        return response()->json(['puntuaciones' => $puntuaciones]);
    }

    // ─── Pilotos ──────────────────────────────────────────────────────────────

    public function comprarPiloto(Request $request, Liga $liga): JsonResponse
    {
        $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
        ]);

        $equipo = $this->miEquipo($request, $liga);
        $piloto = Piloto::findOrFail($request->driver_id);

        if ($equipo->pilotos->contains($piloto->id)) {
            return response()->json(['message' => 'Este piloto ya está en tu equipo'], 422);
        }

        if ($equipo->pilotos->count() >= self::MAX_PILOTOS) {
            return response()->json(['message' => "Ya tienes el máximo de pilotos (" . self::MAX_PILOTOS . ")"], 422);
        }

        if ($piloto->price > $equipo->remaining_budget) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($piloto->price)}, tienes {$this->M($equipo->remaining_budget)}"], 422);
        }

        $equipo->pilotos()->attach($piloto->id, ['role' => 'titular', 'selected_at' => now()]);
        $equipo->decrement('remaining_budget', $piloto->price);

        return response()->json(['message' => "{$piloto->nombre_completo} añadido al equipo"]);
    }

    public function venderPiloto(Request $request, Liga $liga, Piloto $piloto): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        if (!$equipo->pilotos->contains($piloto->id)) {
            return response()->json(['message' => 'El piloto no está en tu equipo'], 422);
        }

        $equipo->pilotos()->updateExistingPivot($piloto->id, ['removed_at' => now()]);
        $equipo->increment('remaining_budget', $piloto->price);

        return response()->json(['message' => "{$piloto->nombre_completo} vendido — {$this->M($piloto->price)} devueltos"]);
    }

    // ─── Director ─────────────────────────────────────────────────────────────

    public function comprarDirector(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['director_id' => ['required', 'exists:team_principals,id']]);

        $equipo   = $this->miEquipo($request, $liga);
        $director = DirectorEquipo::findOrFail($request->director_id);

        if ($equipo->directores->count() >= self::MAX_DIRECTOR) {
            return response()->json(['message' => 'Ya tienes un director. Véndelo primero'], 422);
        }

        if ($director->price > $equipo->remaining_budget) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($director->price)}, tienes {$this->M($equipo->remaining_budget)}"], 422);
        }

        $equipo->directores()->attach($director->id, ['selected_at' => now()]);
        $equipo->decrement('remaining_budget', $director->price);

        return response()->json(['message' => "{$director->name} comprado como director"]);
    }

    public function venderDirector(Request $request, Liga $liga, DirectorEquipo $director): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        if (!$equipo->directores->contains($director->id)) {
            return response()->json(['message' => 'Este director no está en tu equipo'], 422);
        }

        $equipo->directores()->updateExistingPivot($director->id, ['removed_at' => now()]);
        $equipo->increment('remaining_budget', $director->price);

        return response()->json(['message' => "{$director->name} vendido — {$this->M($director->price)} devueltos"]);
    }

    // ─── Escudería ────────────────────────────────────────────────────────────

    public function comprarEscuderia(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['constructor_id' => ['required', 'exists:constructors,id']]);

        $equipo    = $this->miEquipo($request, $liga);
        $escuderia = Escuderia::findOrFail($request->constructor_id);

        // Si ya tiene una la vendemos primero automáticamente
        $actual = $equipo->escuderias->first();
        if ($actual) {
            if ($actual->id === $escuderia->id) {
                return response()->json(['message' => 'Esta escudería ya está en tu equipo'], 422);
            }
            $equipo->escuderias()->updateExistingPivot($actual->id, ['removed_at' => now()]);
            $equipo->increment('remaining_budget', $actual->price);
            $equipo->refresh();
        }

        if ($escuderia->price > $equipo->remaining_budget) {
            if ($actual) {
                $equipo->escuderias()->attach($actual->id, ['selected_at' => now()]);
                $equipo->decrement('remaining_budget', $actual->price);
            }
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($escuderia->price)}, tienes {$this->M($equipo->remaining_budget)}"], 422);
        }

        $equipo->escuderias()->attach($escuderia->id, ['selected_at' => now()]);
        $equipo->decrement('remaining_budget', $escuderia->price);

        return response()->json(['message' => "{$escuderia->name} comprada"]);
    }

    public function venderEscuderia(Request $request, Liga $liga, Escuderia $escuderia): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        if (!$equipo->escuderias->contains($escuderia->id)) {
            return response()->json(['message' => 'Esta escudería no está en tu equipo'], 422);
        }

        $equipo->escuderias()->updateExistingPivot($escuderia->id, ['removed_at' => now()]);
        $equipo->increment('remaining_budget', $escuderia->price);

        return response()->json(['message' => "{$escuderia->name} vendida — {$this->M($escuderia->price)} devueltos"]);
    }

    // ─── Robar pilotos/escuderías/directores ──────────────────────────────────

    public function robarPiloto(Request $request, Liga $liga): JsonResponse
    {
        $request->validate([
            'piloto_id' => ['required', 'exists:drivers,id'],
        ]);

        $equipoComprador = $this->miEquipo($request, $liga);
        $piloto          = Piloto::findOrFail($request->piloto_id);

        if ($equipoComprador->pilotos->contains($piloto->id)) {
            return response()->json(['message' => 'Este piloto ya está en tu equipo'], 422);
        }

        if ($equipoComprador->pilotos->count() >= self::MAX_PILOTOS) {
            return response()->json(['message' => "Ya tienes el máximo de pilotos (" . self::MAX_PILOTOS . ")"], 422);
        }

        // Buscar el equipo propietario en la misma liga
        $pivotPropietario = DB::table('fantasy_team_drivers')
            ->join('fantasy_teams', 'fantasy_teams.id', '=', 'fantasy_team_drivers.fantasy_team_id')
            ->where('fantasy_teams.league_id', $liga->id)
            ->where('fantasy_team_drivers.driver_id', $piloto->id)
            ->whereNull('fantasy_team_drivers.removed_at')
            ->where('fantasy_team_drivers.fantasy_team_id', '!=', $equipoComprador->id)
            ->select('fantasy_team_drivers.*', 'fantasy_teams.user_id as owner_user_id')
            ->first();

        if (!$pivotPropietario) {
            return response()->json(['message' => 'Este piloto no está en ningún equipo de esta liga'], 422);
        }

        // Verificar protección de 7 días
        if ($pivotPropietario->selected_at && now()->diffInDays($pivotPropietario->selected_at) < 7) {
            $diasRestantes = 7 - (int) now()->diffInDays($pivotPropietario->selected_at);
            return response()->json(['message' => "Este piloto está protegido. Quedan {$diasRestantes} días de protección"], 422);
        }

        $precio = $piloto->price;

        if ($precio > $equipoComprador->remaining_budget) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($precio)}, tienes {$this->M($equipoComprador->remaining_budget)}"], 422);
        }

        DB::transaction(function () use ($equipoComprador, $piloto, $pivotPropietario, $precio) {
            DB::table('fantasy_team_drivers')
                ->where('fantasy_team_id', $pivotPropietario->fantasy_team_id)
                ->where('driver_id', $piloto->id)
                ->whereNull('removed_at')
                ->update(['removed_at' => now()]);

            EquipoFantasy::where('id', $pivotPropietario->fantasy_team_id)
                ->increment('remaining_budget', $precio);

            $equipoComprador->pilotos()->attach($piloto->id, [
                'role'        => 'titular',
                'selected_at' => now(),
            ]);

            $equipoComprador->decrement('remaining_budget', $precio);
        });

        return response()->json(['message' => "{$piloto->nombre_completo} robado"]);
    }

    public function robarEscuderia(Request $request, Liga $liga): JsonResponse
    {
        $request->validate([
            'escuderia_id' => ['required', 'exists:constructors,id'],
        ]);

        $equipoComprador = $this->miEquipo($request, $liga);
        $escuderia       = Escuderia::findOrFail($request->escuderia_id);

        if ($equipoComprador->escuderias->contains($escuderia->id)) {
            return response()->json(['message' => 'Esta escudería ya está en tu equipo'], 422);
        }

        $pivotPropietario = DB::table('fantasy_team_constructors')
            ->join('fantasy_teams', 'fantasy_teams.id', '=', 'fantasy_team_constructors.fantasy_team_id')
            ->where('fantasy_teams.league_id', $liga->id)
            ->where('fantasy_team_constructors.constructor_id', $escuderia->id)
            ->whereNull('fantasy_team_constructors.removed_at')
            ->where('fantasy_team_constructors.fantasy_team_id', '!=', $equipoComprador->id)
            ->select('fantasy_team_constructors.*', 'fantasy_teams.user_id as owner_user_id')
            ->first();

        if (!$pivotPropietario) {
            return response()->json(['message' => 'Esta escudería no está en ningún equipo de esta liga'], 422);
        }

        if ($pivotPropietario->selected_at && now()->diffInDays($pivotPropietario->selected_at) < 7) {
            $diasRestantes = 7 - (int) now()->diffInDays($pivotPropietario->selected_at);
            return response()->json(['message' => "Esta escudería está protegida. Quedan {$diasRestantes} días de protección"], 422);
        }

        $precio = $escuderia->price;

        if ($precio > $equipoComprador->remaining_budget) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($precio)}, tienes {$this->M($equipoComprador->remaining_budget)}"], 422);
        }

        DB::transaction(function () use ($equipoComprador, $escuderia, $pivotPropietario, $precio) {
            $escuderiaActual = $equipoComprador->escuderias->first();
            if ($escuderiaActual) {
                $equipoComprador->escuderias()->updateExistingPivot($escuderiaActual->id, ['removed_at' => now()]);
                $equipoComprador->increment('remaining_budget', $escuderiaActual->price);
            }

            DB::table('fantasy_team_constructors')
                ->where('fantasy_team_id', $pivotPropietario->fantasy_team_id)
                ->where('constructor_id', $escuderia->id)
                ->whereNull('removed_at')
                ->update(['removed_at' => now()]);

            EquipoFantasy::where('id', $pivotPropietario->fantasy_team_id)
                ->increment('remaining_budget', $precio);

            $equipoComprador->escuderias()->attach($escuderia->id, ['selected_at' => now()]);
            $equipoComprador->decrement('remaining_budget', $precio);
        });

        return response()->json(['message' => "{$escuderia->name} robada"]);
    }

    public function robarDirector(Request $request, Liga $liga): JsonResponse
    {
        $request->validate([
            'director_id' => ['required', 'exists:team_principals,id'],
        ]);

        $equipoComprador = $this->miEquipo($request, $liga);
        $director        = DirectorEquipo::findOrFail($request->director_id);

        if ($equipoComprador->directores->contains($director->id)) {
            return response()->json(['message' => 'Este director ya está en tu equipo'], 422);
        }

        $pivotPropietario = DB::table('fantasy_team_principals')
            ->join('fantasy_teams', 'fantasy_teams.id', '=', 'fantasy_team_principals.fantasy_team_id')
            ->where('fantasy_teams.league_id', $liga->id)
            ->where('fantasy_team_principals.team_principal_id', $director->id)
            ->whereNull('fantasy_team_principals.removed_at')
            ->where('fantasy_team_principals.fantasy_team_id', '!=', $equipoComprador->id)
            ->select('fantasy_team_principals.*', 'fantasy_teams.user_id as owner_user_id')
            ->first();

        if (!$pivotPropietario) {
            return response()->json(['message' => 'Este director no está en ningún equipo de esta liga'], 422);
        }

        if ($pivotPropietario->selected_at && now()->diffInDays($pivotPropietario->selected_at) < 7) {
            $diasRestantes = 7 - (int) now()->diffInDays($pivotPropietario->selected_at);
            return response()->json(['message' => "Este director está protegido. Quedan {$diasRestantes} días de protección"], 422);
        }

        $precio = $director->price;

        if ($precio > $equipoComprador->remaining_budget) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($precio)}, tienes {$this->M($equipoComprador->remaining_budget)}"], 422);
        }

        if ($equipoComprador->directores->count() >= self::MAX_DIRECTOR) {
            return response()->json(['message' => 'Ya tienes un director. Véndelo primero'], 422);
        }

        DB::transaction(function () use ($equipoComprador, $director, $pivotPropietario, $precio) {
            DB::table('fantasy_team_principals')
                ->where('fantasy_team_id', $pivotPropietario->fantasy_team_id)
                ->where('team_principal_id', $director->id)
                ->whereNull('removed_at')
                ->update(['removed_at' => now()]);

            EquipoFantasy::where('id', $pivotPropietario->fantasy_team_id)
                ->increment('remaining_budget', $precio);

            $equipoComprador->directores()->attach($director->id, ['selected_at' => now()]);
            $equipoComprador->decrement('remaining_budget', $precio);
        });

        return response()->json(['message' => "{$director->name} robado"]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function miEquipo(Request $request, Liga $liga): EquipoFantasy
    {
        return EquipoFantasy::where('league_id', $liga->id)
            ->where('user_id', $request->user()->id)
            ->with(['pilotos.escuderia', 'escuderias', 'directores.escuderia'])
            ->firstOrFail();
    }

    private function M(int $precio): string
    {
        return number_format($precio / 1_000_000, 1) . 'M €';
    }
}
