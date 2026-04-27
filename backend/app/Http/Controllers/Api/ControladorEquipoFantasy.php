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
            'presupuesto_restante' => $equipo->presupuesto_restante,
        ]);
    }

    public function showDeUsuario(Request $request, Liga $liga, int $userId): JsonResponse
    {
        $esMiembro = $liga->miembros()->where('usuario_id', $request->user()->id)->exists();
        if (!$esMiembro) {
            return response()->json(['message' => 'No eres miembro de esta liga'], 403);
        }

        $equipo = EquipoFantasy::where('liga_id', $liga->id)
            ->where('usuario_id', $userId)
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

        $registros = PuntosEquipoCarrera::where('equipo_fantasy_id', $equipo->id)
            ->with('carrera')
            ->orderByDesc('carrera_id')
            ->get();

        $pilotoIds    = [];
        $escuderiaIds = [];
        $directorIds  = [];

        foreach ($registros as $reg) {
            $b = $reg->desglose ?? [];
            $pilotoIds    = array_merge($pilotoIds,    array_keys($b['pilotos']    ?? []));
            $escuderiaIds = array_merge($escuderiaIds, array_keys($b['escuderias'] ?? []));
            $directorIds  = array_merge($directorIds,  array_keys($b['directores'] ?? []));
        }

        $pilotos    = Piloto::whereIn('id', array_unique($pilotoIds))->with('escuderia')->get()->keyBy('id');
        $escuderias = Escuderia::whereIn('id', array_unique($escuderiaIds))->get()->keyBy('id');
        $directores = DirectorEquipo::whereIn('id', array_unique($directorIds))->with('escuderia')->get()->keyBy('id');

        $puntuaciones = $registros->map(function ($reg) use ($pilotos, $escuderias, $directores) {
            $b = $reg->desglose ?? [];

            $desglosePilotos = collect($b['pilotos'] ?? [])->map(function ($datos, $id) use ($pilotos) {
                $p = $pilotos[(int) $id] ?? null;
                return [
                    'id'     => (int) $id,
                    'nombre' => $p ? "{$p->nombre} {$p->apellido}" : "Piloto #{$id}",
                    'color'  => $p?->escuderia?->color,
                    'total'  => $datos['total'],
                ];
            })->values();

            $desgloseEscuderias = collect($b['escuderias'] ?? [])->map(function ($datos, $id) use ($escuderias) {
                $e = $escuderias[(int) $id] ?? null;
                return [
                    'id'     => (int) $id,
                    'nombre' => $e?->nombre ?? "Escudería #{$id}",
                    'color'  => $e?->color,
                    'total'  => $datos['total'],
                ];
            })->values();

            $desgloseDirectores = collect($b['directores'] ?? [])->map(function ($datos, $id) use ($directores) {
                $d = $directores[(int) $id] ?? null;
                return [
                    'id'        => (int) $id,
                    'nombre'    => $d?->nombre ?? "Director #{$id}",
                    'escuderia' => $d?->escuderia?->nombre,
                    'total'     => $datos['total'],
                ];
            })->values();

            return [
                'carrera_id'     => $reg->carrera_id,
                'carrera_nombre' => $reg->carrera?->nombre ?? 'Carrera',
                'carrera_fecha'  => $reg->carrera?->fecha,
                'puntos_total'   => $reg->puntos_obtenidos,
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
            'piloto_id' => ['required', 'exists:pilotos,id'],
        ]);

        $equipo = $this->miEquipo($request, $liga);
        $piloto = Piloto::findOrFail($request->piloto_id);

        if ($equipo->pilotos->contains($piloto->id)) {
            return response()->json(['message' => 'Este piloto ya está en tu equipo'], 422);
        }

        if ($equipo->pilotos->count() >= self::MAX_PILOTOS) {
            return response()->json(['message' => "Ya tienes el máximo de pilotos (" . self::MAX_PILOTOS . ")"], 422);
        }

        if ($piloto->precio > $equipo->presupuesto_restante) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($piloto->precio)}, tienes {$this->M($equipo->presupuesto_restante)}"], 422);
        }

        $equipo->pilotos()->attach($piloto->id, ['rol' => 'titular', 'fecha_seleccion' => now()]);
        $equipo->decrement('presupuesto_restante', $piloto->precio);

        return response()->json(['message' => "{$piloto->nombre_completo} añadido al equipo"]);
    }

    public function venderPiloto(Request $request, Liga $liga, Piloto $piloto): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        if (!$equipo->pilotos->contains($piloto->id)) {
            return response()->json(['message' => 'El piloto no está en tu equipo'], 422);
        }

        $equipo->pilotos()->updateExistingPivot($piloto->id, ['fecha_baja' => now()]);
        $equipo->increment('presupuesto_restante', $piloto->precio);

        return response()->json(['message' => "{$piloto->nombre_completo} vendido — {$this->M($piloto->precio)} devueltos"]);
    }

    // ─── Director ─────────────────────────────────────────────────────────────

    public function comprarDirector(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['director_id' => ['required', 'exists:directores_equipo,id']]);

        $equipo   = $this->miEquipo($request, $liga);
        $director = DirectorEquipo::findOrFail($request->director_id);

        if ($equipo->directores->count() >= self::MAX_DIRECTOR) {
            return response()->json(['message' => 'Ya tienes un director. Véndelo primero'], 422);
        }

        if ($director->precio > $equipo->presupuesto_restante) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($director->precio)}, tienes {$this->M($equipo->presupuesto_restante)}"], 422);
        }

        $equipo->directores()->attach($director->id, ['fecha_seleccion' => now()]);
        $equipo->decrement('presupuesto_restante', $director->precio);

        return response()->json(['message' => "{$director->nombre} comprado como director"]);
    }

    public function venderDirector(Request $request, Liga $liga, DirectorEquipo $director): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        if (!$equipo->directores->contains($director->id)) {
            return response()->json(['message' => 'Este director no está en tu equipo'], 422);
        }

        $equipo->directores()->updateExistingPivot($director->id, ['fecha_baja' => now()]);
        $equipo->increment('presupuesto_restante', $director->precio);

        return response()->json(['message' => "{$director->nombre} vendido — {$this->M($director->precio)} devueltos"]);
    }

    // ─── Escudería ────────────────────────────────────────────────────────────

    public function comprarEscuderia(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['escuderia_id' => ['required', 'exists:escuderias,id']]);

        $equipo    = $this->miEquipo($request, $liga);
        $escuderia = Escuderia::findOrFail($request->escuderia_id);

        $actual = $equipo->escuderias->first();
        if ($actual) {
            if ($actual->id === $escuderia->id) {
                return response()->json(['message' => 'Esta escudería ya está en tu equipo'], 422);
            }
            $equipo->escuderias()->updateExistingPivot($actual->id, ['fecha_baja' => now()]);
            $equipo->increment('presupuesto_restante', $actual->precio);
            $equipo->refresh();
        }

        if ($escuderia->precio > $equipo->presupuesto_restante) {
            if ($actual) {
                $equipo->escuderias()->attach($actual->id, ['fecha_seleccion' => now()]);
                $equipo->decrement('presupuesto_restante', $actual->precio);
            }
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($escuderia->precio)}, tienes {$this->M($equipo->presupuesto_restante)}"], 422);
        }

        $equipo->escuderias()->attach($escuderia->id, ['fecha_seleccion' => now()]);
        $equipo->decrement('presupuesto_restante', $escuderia->precio);

        return response()->json(['message' => "{$escuderia->nombre} comprada"]);
    }

    public function venderEscuderia(Request $request, Liga $liga, Escuderia $escuderia): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        if (!$equipo->escuderias->contains($escuderia->id)) {
            return response()->json(['message' => 'Esta escudería no está en tu equipo'], 422);
        }

        $equipo->escuderias()->updateExistingPivot($escuderia->id, ['fecha_baja' => now()]);
        $equipo->increment('presupuesto_restante', $escuderia->precio);

        return response()->json(['message' => "{$escuderia->nombre} vendida — {$this->M($escuderia->precio)} devueltos"]);
    }

    // ─── Robar ────────────────────────────────────────────────────────────────

    public function robarPiloto(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['piloto_id' => ['required', 'exists:pilotos,id']]);

        $equipoComprador = $this->miEquipo($request, $liga);
        $piloto          = Piloto::findOrFail($request->piloto_id);

        if ($equipoComprador->pilotos->contains($piloto->id)) {
            return response()->json(['message' => 'Este piloto ya está en tu equipo'], 422);
        }

        if ($equipoComprador->pilotos->count() >= self::MAX_PILOTOS) {
            return response()->json(['message' => "Ya tienes el máximo de pilotos (" . self::MAX_PILOTOS . ")"], 422);
        }

        $pivotPropietario = DB::table('equipos_fantasy_pilotos')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_pilotos.equipo_fantasy_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy_pilotos.piloto_id', $piloto->id)
            ->whereNull('equipos_fantasy_pilotos.fecha_baja')
            ->where('equipos_fantasy_pilotos.equipo_fantasy_id', '!=', $equipoComprador->id)
            ->select('equipos_fantasy_pilotos.*', 'equipos_fantasy.usuario_id as propietario_usuario_id')
            ->first();

        if (!$pivotPropietario) {
            return response()->json(['message' => 'Este piloto no está en ningún equipo de esta liga'], 422);
        }

        if ($pivotPropietario->fecha_seleccion && now()->diffInDays($pivotPropietario->fecha_seleccion) < 7) {
            $diasRestantes = 7 - (int) now()->diffInDays($pivotPropietario->fecha_seleccion);
            return response()->json(['message' => "Este piloto está protegido. Quedan {$diasRestantes} días de protección"], 422);
        }

        $precio = $piloto->precio;

        if ($precio > $equipoComprador->presupuesto_restante) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($precio)}, tienes {$this->M($equipoComprador->presupuesto_restante)}"], 422);
        }

        DB::transaction(function () use ($equipoComprador, $piloto, $pivotPropietario, $precio) {
            DB::table('equipos_fantasy_pilotos')
                ->where('equipo_fantasy_id', $pivotPropietario->equipo_fantasy_id)
                ->where('piloto_id', $piloto->id)
                ->whereNull('fecha_baja')
                ->update(['fecha_baja' => now()]);

            EquipoFantasy::where('id', $pivotPropietario->equipo_fantasy_id)
                ->increment('presupuesto_restante', $precio);

            $equipoComprador->pilotos()->attach($piloto->id, [
                'rol'             => 'titular',
                'fecha_seleccion' => now(),
            ]);

            $equipoComprador->decrement('presupuesto_restante', $precio);
        });

        return response()->json(['message' => "{$piloto->nombre_completo} robado"]);
    }

    public function robarEscuderia(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['escuderia_id' => ['required', 'exists:escuderias,id']]);

        $equipoComprador = $this->miEquipo($request, $liga);
        $escuderia       = Escuderia::findOrFail($request->escuderia_id);

        if ($equipoComprador->escuderias->contains($escuderia->id)) {
            return response()->json(['message' => 'Esta escudería ya está en tu equipo'], 422);
        }

        $pivotPropietario = DB::table('equipos_fantasy_escuderias')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_escuderias.equipo_fantasy_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy_escuderias.escuderia_id', $escuderia->id)
            ->whereNull('equipos_fantasy_escuderias.fecha_baja')
            ->where('equipos_fantasy_escuderias.equipo_fantasy_id', '!=', $equipoComprador->id)
            ->select('equipos_fantasy_escuderias.*', 'equipos_fantasy.usuario_id as propietario_usuario_id')
            ->first();

        if (!$pivotPropietario) {
            return response()->json(['message' => 'Esta escudería no está en ningún equipo de esta liga'], 422);
        }

        if ($pivotPropietario->fecha_seleccion && now()->diffInDays($pivotPropietario->fecha_seleccion) < 7) {
            $diasRestantes = 7 - (int) now()->diffInDays($pivotPropietario->fecha_seleccion);
            return response()->json(['message' => "Esta escudería está protegida. Quedan {$diasRestantes} días de protección"], 422);
        }

        $precio = $escuderia->precio;

        if ($precio > $equipoComprador->presupuesto_restante) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($precio)}, tienes {$this->M($equipoComprador->presupuesto_restante)}"], 422);
        }

        DB::transaction(function () use ($equipoComprador, $escuderia, $pivotPropietario, $precio) {
            $escuderiaActual = $equipoComprador->escuderias->first();
            if ($escuderiaActual) {
                $equipoComprador->escuderias()->updateExistingPivot($escuderiaActual->id, ['fecha_baja' => now()]);
                $equipoComprador->increment('presupuesto_restante', $escuderiaActual->precio);
            }

            DB::table('equipos_fantasy_escuderias')
                ->where('equipo_fantasy_id', $pivotPropietario->equipo_fantasy_id)
                ->where('escuderia_id', $escuderia->id)
                ->whereNull('fecha_baja')
                ->update(['fecha_baja' => now()]);

            EquipoFantasy::where('id', $pivotPropietario->equipo_fantasy_id)
                ->increment('presupuesto_restante', $precio);

            $equipoComprador->escuderias()->attach($escuderia->id, ['fecha_seleccion' => now()]);
            $equipoComprador->decrement('presupuesto_restante', $precio);
        });

        return response()->json(['message' => "{$escuderia->nombre} robada"]);
    }

    public function robarDirector(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['director_id' => ['required', 'exists:directores_equipo,id']]);

        $equipoComprador = $this->miEquipo($request, $liga);
        $director        = DirectorEquipo::findOrFail($request->director_id);

        if ($equipoComprador->directores->contains($director->id)) {
            return response()->json(['message' => 'Este director ya está en tu equipo'], 422);
        }

        $pivotPropietario = DB::table('equipos_fantasy_directores')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_directores.equipo_fantasy_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy_directores.director_id', $director->id)
            ->whereNull('equipos_fantasy_directores.fecha_baja')
            ->where('equipos_fantasy_directores.equipo_fantasy_id', '!=', $equipoComprador->id)
            ->select('equipos_fantasy_directores.*', 'equipos_fantasy.usuario_id as propietario_usuario_id')
            ->first();

        if (!$pivotPropietario) {
            return response()->json(['message' => 'Este director no está en ningún equipo de esta liga'], 422);
        }

        if ($pivotPropietario->fecha_seleccion && now()->diffInDays($pivotPropietario->fecha_seleccion) < 7) {
            $diasRestantes = 7 - (int) now()->diffInDays($pivotPropietario->fecha_seleccion);
            return response()->json(['message' => "Este director está protegido. Quedan {$diasRestantes} días de protección"], 422);
        }

        $precio = $director->precio;

        if ($precio > $equipoComprador->presupuesto_restante) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($precio)}, tienes {$this->M($equipoComprador->presupuesto_restante)}"], 422);
        }

        if ($equipoComprador->directores->count() >= self::MAX_DIRECTOR) {
            return response()->json(['message' => 'Ya tienes un director. Véndelo primero'], 422);
        }

        DB::transaction(function () use ($equipoComprador, $director, $pivotPropietario, $precio) {
            DB::table('equipos_fantasy_directores')
                ->where('equipo_fantasy_id', $pivotPropietario->equipo_fantasy_id)
                ->where('director_id', $director->id)
                ->whereNull('fecha_baja')
                ->update(['fecha_baja' => now()]);

            EquipoFantasy::where('id', $pivotPropietario->equipo_fantasy_id)
                ->increment('presupuesto_restante', $precio);

            $equipoComprador->directores()->attach($director->id, ['fecha_seleccion' => now()]);
            $equipoComprador->decrement('presupuesto_restante', $precio);
        });

        return response()->json(['message' => "{$director->nombre} robado"]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function miEquipo(Request $request, Liga $liga): EquipoFantasy
    {
        return EquipoFantasy::where('liga_id', $liga->id)
            ->where('usuario_id', $request->user()->id)
            ->with(['pilotos.escuderia', 'escuderias', 'directores.escuderia'])
            ->firstOrFail();
    }

    private function M(int $precio): string
    {
        return number_format($precio / 1_000_000, 1) . 'M €';
    }
}
