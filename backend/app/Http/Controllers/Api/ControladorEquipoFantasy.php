<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coche;
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
    private const MAX_PILOTOS   = 2;
    private const MAX_COCHE     = 1;
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
            ->with(['pilotos.escuderia', 'escuderias', 'coches.escuderia', 'usuario'])
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
        $cocheIds     = [];

        foreach ($registros as $reg) {
            $b = $reg->desglose ?? [];
            $pilotoIds    = array_merge($pilotoIds,    array_keys($b['pilotos']    ?? []));
            $escuderiaIds = array_merge($escuderiaIds, array_keys($b['escuderias'] ?? []));
            $cocheIds     = array_merge($cocheIds,     array_keys($b['coches']     ?? []));
        }

        $pilotos    = Piloto::whereIn('id', array_unique($pilotoIds))->with('escuderia')->get()->keyBy('id');
        $escuderias = Escuderia::whereIn('id', array_unique($escuderiaIds))->get()->keyBy('id');
        $coches     = Coche::whereIn('id', array_unique($cocheIds))->with('escuderia')->get()->keyBy('id');

        $puntuaciones = $registros->map(function ($reg) use ($pilotos, $escuderias, $coches) {
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

            $desgloseCoches = collect($b['coches'] ?? [])->map(function ($datos, $id) use ($coches) {
                $c = $coches[(int) $id] ?? null;
                return [
                    'id'        => (int) $id,
                    'nombre'    => $c?->nombre ?? "Coche #{$id}",
                    'escuderia' => $c?->escuderia?->nombre,
                    'color'     => $c?->escuderia?->color,
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
                    'coches'     => $desgloseCoches,
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

        // Verificar que ningún otro equipo de la liga tiene este piloto activo
        $enOtroEquipo = DB::table('equipos_fantasy_pilotos')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_pilotos.equipo_fantasy_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy_pilotos.piloto_id', $piloto->id)
            ->where('equipos_fantasy_pilotos.equipo_fantasy_id', '!=', $equipo->id)
            ->whereNull('equipos_fantasy_pilotos.fecha_baja')
            ->exists();

        if ($enOtroEquipo) {
            return response()->json(['message' => 'Este piloto ya está en otro equipo. Usa la opción Robar'], 422);
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

    // ─── Coche ────────────────────────────────────────────────────────────────

    public function comprarCoche(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['coche_id' => ['required', 'exists:coches_equipo,id']]);

        $equipo = $this->miEquipo($request, $liga);
        $coche  = Coche::findOrFail($request->coche_id);

        if ($equipo->coches->count() >= self::MAX_COCHE) {
            return response()->json(['message' => 'Ya tienes un coche. Véndelo primero'], 422);
        }

        // Verificar que ningún otro equipo de la liga tiene este coche activo
        $enOtroEquipo = DB::table('equipos_fantasy_coches')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_coches.equipo_fantasy_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy_coches.coche_id', $coche->id)
            ->where('equipos_fantasy_coches.equipo_fantasy_id', '!=', $equipo->id)
            ->whereNull('equipos_fantasy_coches.fecha_baja')
            ->exists();

        if ($enOtroEquipo) {
            return response()->json(['message' => 'Este coche ya está en otro equipo. Usa la opción Robar'], 422);
        }

        if ($coche->precio > $equipo->presupuesto_restante) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($coche->precio)}, tienes {$this->M($equipo->presupuesto_restante)}"], 422);
        }

        $equipo->coches()->attach($coche->id, ['fecha_seleccion' => now()]);
        $equipo->decrement('presupuesto_restante', $coche->precio);

        return response()->json(['message' => "{$coche->nombre} añadido al equipo"]);
    }

    public function venderCoche(Request $request, Liga $liga, Coche $coche): JsonResponse
    {
        $equipo = $this->miEquipo($request, $liga);

        if (!$equipo->coches->contains($coche->id)) {
            return response()->json(['message' => 'Este coche no está en tu equipo'], 422);
        }

        $equipo->coches()->updateExistingPivot($coche->id, ['fecha_baja' => now()]);
        $equipo->increment('presupuesto_restante', $coche->precio);

        return response()->json(['message' => "{$coche->nombre} vendido — {$this->M($coche->precio)} devueltos"]);
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

        // Verificar que ningún otro equipo de la liga tiene esta escudería activa
        $enOtroEquipo = DB::table('equipos_fantasy_escuderias')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_escuderias.equipo_fantasy_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy_escuderias.escuderia_id', $escuderia->id)
            ->where('equipos_fantasy_escuderias.equipo_fantasy_id', '!=', $equipo->id)
            ->whereNull('equipos_fantasy_escuderias.fecha_baja')
            ->exists();

        if ($enOtroEquipo) {
            if ($actual) {
                $equipo->escuderias()->attach($actual->id, ['fecha_seleccion' => now()]);
                $equipo->decrement('presupuesto_restante', $actual->precio);
            }
            return response()->json(['message' => 'Esta escudería ya está en otro equipo. Usa la opción Robar'], 422);
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

    public function robarCoche(Request $request, Liga $liga): JsonResponse
    {
        $request->validate(['coche_id' => ['required', 'exists:coches_equipo,id']]);

        $equipoComprador = $this->miEquipo($request, $liga);
        $coche           = Coche::findOrFail($request->coche_id);

        if ($equipoComprador->coches->contains($coche->id)) {
            return response()->json(['message' => 'Este coche ya está en tu equipo'], 422);
        }

        $pivotPropietario = DB::table('equipos_fantasy_coches')
            ->join('equipos_fantasy', 'equipos_fantasy.id', '=', 'equipos_fantasy_coches.equipo_fantasy_id')
            ->where('equipos_fantasy.liga_id', $liga->id)
            ->where('equipos_fantasy_coches.coche_id', $coche->id)
            ->whereNull('equipos_fantasy_coches.fecha_baja')
            ->where('equipos_fantasy_coches.equipo_fantasy_id', '!=', $equipoComprador->id)
            ->select('equipos_fantasy_coches.*', 'equipos_fantasy.usuario_id as propietario_usuario_id')
            ->first();

        if (!$pivotPropietario) {
            return response()->json(['message' => 'Este coche no está en ningún equipo de esta liga'], 422);
        }

        if ($pivotPropietario->fecha_seleccion && now()->diffInDays($pivotPropietario->fecha_seleccion) < 7) {
            $diasRestantes = 7 - (int) now()->diffInDays($pivotPropietario->fecha_seleccion);
            return response()->json(['message' => "Este coche está protegido. Quedan {$diasRestantes} días de protección"], 422);
        }

        $precio = $coche->precio;

        if ($precio > $equipoComprador->presupuesto_restante) {
            return response()->json(['message' => "Sin presupuesto. Necesitas {$this->M($precio)}, tienes {$this->M($equipoComprador->presupuesto_restante)}"], 422);
        }

        if ($equipoComprador->coches->count() >= self::MAX_COCHE) {
            return response()->json(['message' => 'Ya tienes un coche. Véndelo primero'], 422);
        }

        DB::transaction(function () use ($equipoComprador, $coche, $pivotPropietario, $precio) {
            DB::table('equipos_fantasy_coches')
                ->where('equipo_fantasy_id', $pivotPropietario->equipo_fantasy_id)
                ->where('coche_id', $coche->id)
                ->whereNull('fecha_baja')
                ->update(['fecha_baja' => now()]);

            EquipoFantasy::where('id', $pivotPropietario->equipo_fantasy_id)
                ->increment('presupuesto_restante', $precio);

            $equipoComprador->coches()->attach($coche->id, ['fecha_seleccion' => now()]);
            $equipoComprador->decrement('presupuesto_restante', $precio);
        });

        return response()->json(['message' => "{$coche->nombre} robado"]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function miEquipo(Request $request, Liga $liga): EquipoFantasy
    {
        return EquipoFantasy::where('liga_id', $liga->id)
            ->where('usuario_id', $request->user()->id)
            ->with(['pilotos.escuderia', 'escuderias', 'coches.escuderia'])
            ->firstOrFail();
    }

    private function M(int $precio): string
    {
        return number_format($precio / 1_000_000, 1) . 'M €';
    }
}
