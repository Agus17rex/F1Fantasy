<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\EquipoFantasy;
use App\Models\Liga;
use App\Models\MiembroLiga;
use App\Models\PuntosEquipoCarrera;
use App\Models\ResultadoCarrera;
use App\Models\User;
use App\Services\ServicioApiF1;
use App\Services\ServicioPuntuacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ControladorAdminCarrera extends Controller
{
    public function __construct(
        private ServicioApiF1      $servicioApi,
        private ServicioPuntuacion $servicioPuntuacion
    ) {}

    public function sincronizarDatosF1(Request $request): JsonResponse
    {
        $temporada = $request->get('season', date('Y'));

        $totalEscuderias = $this->servicioApi->sincronizarEscuderias($temporada);
        $totalPilotos    = $this->servicioApi->sincronizarPilotos($temporada);
        $totalCarreras   = $this->servicioApi->sincronizarCarreras($temporada);

        return response()->json([
            'message'    => 'Datos sincronizados correctamente',
            'pilotos'    => $totalPilotos,
            'escuderias' => $totalEscuderias,
            'carreras'   => $totalCarreras,
        ]);
    }

    public function sincronizarResultados(Carrera $carrera): JsonResponse
    {
        $sincronizados = $this->servicioApi->sincronizarResultadosCarrera($carrera);

        return response()->json([
            'message' => "Resultados sincronizados: {$sincronizados} registros",
            'carrera' => $carrera->load('resultados'),
        ]);
    }

    public function puntuarCarrera(Carrera $carrera): JsonResponse
    {
        if ($carrera->resultados->isEmpty()) {
            return response()->json(['message' => 'No hay resultados para esta carrera'], 422);
        }

        if ($carrera->estado === 'scored') {
            return response()->json(['message' => 'Esta carrera ya ha sido puntuada. Usa "Recalcular todo" para actualizar.'], 422);
        }

        $this->servicioPuntuacion->procesarPuntosCarrera($carrera);

        return response()->json([
            'message' => "Puntos calculados para: {$carrera->nombre}",
            'carrera' => $carrera->fresh(),
        ]);
    }

    /**
     * Marca/desmarca penalizaciones manuales (grid, tiempo) en un resultado concreto.
     * El admin llama a esto ANTES de puntuar, o después y luego lanza "recalcular todo".
     */
    public function actualizarPenalizaciones(ResultadoCarrera $resultado, Request $request): JsonResponse
    {
        $data = $request->validate([
            'penalizacion_grid'   => 'boolean',
            'penalizacion_tiempo' => 'boolean',
        ]);

        $resultado->update($data);

        return response()->json([
            'message'   => 'Penalizaciones actualizadas',
            'resultado' => $resultado->only(['id', 'penalizacion_grid', 'penalizacion_tiempo']),
        ]);
    }

    /**
     * Recalcula todos los puntos desde cero:
     *  1. Recalcula puntos individuales de cada resultado
     *  2. Resetea totales de equipos y miembros
     *  3. Vuelve a calcular puntos de equipo para cada carrera ya puntuada
     */
    public function recalcularTodo(): JsonResponse
    {
        $carreras = Carrera::where('estado', 'scored')
            ->with('resultados')
            ->orderBy('fecha')
            ->get();

        // Paso 1: recalcular puntos base de cada resultado
        $resultados = ResultadoCarrera::all();
        foreach ($resultados as $resultado) {
            $this->servicioPuntuacion->calcularPuntosPiloto($resultado);
        }

        // Paso 2: aplicar BEATS_TEAMMATE por carrera
        foreach ($carreras as $carrera) {
            $this->servicioPuntuacion->aplicarBeatsTeammate($carrera);
        }

        // Paso 3: resetear totales
        PuntosEquipoCarrera::query()->delete();
        EquipoFantasy::query()->update(['puntos_totales' => 0]);
        MiembroLiga::query()->update(['puntos_totales' => 0]);

        // Paso 4: recalcular por carrera puntuada
        foreach ($carreras as $carrera) {
            $equipos = EquipoFantasy::with(['miembroLiga'])->get();
            DB::transaction(function () use ($carrera, $equipos) {
                foreach ($equipos as $equipo) {
                    $this->servicioPuntuacion->calcularPuntosEquipo($equipo, $carrera);
                }
            });
        }

        return response()->json(['message' => 'Recálculo completado correctamente']);
    }

    public function carreras(): JsonResponse
    {
        $temporada = Carrera::max('temporada') ?? now()->year;

        $carreras = Carrera::with(['circuito', 'resultados'])
            ->withCount('resultados')
            ->where('temporada', $temporada)
            ->orderBy('fecha')
            ->get();

        return response()->json($carreras);
    }

    public function actualizarPrecios(Request $request): JsonResponse
    {
        $temporada = (int) $request->input('season', date('Y'));

        try {
            $this->servicioApi->actualizarPrecios($temporada);
        } catch (\Throwable $e) {
            Log::error('Error al recalcular precios', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al conectar con la API de F1: ' . $e->getMessage(),
            ], 502);
        }

        return response()->json(['message' => 'Precios recalculados correctamente según la clasificación del campeonato']);
    }

    public function puntuacionCarrera(Carrera $carrera): JsonResponse
    {
        $reglas = \App\Models\ReglaPuntuacion::where('activa', true)->get()->keyBy('evento');

        $resultados = $carrera->resultados()
            ->with(['piloto', 'escuderia'])
            ->orderBy('posicion_final')
            ->get();

        $filas = $resultados->map(fn($r) => [
            'id'                     => $r->id,
            'piloto'                 => $r->piloto?->nombre . ' ' . $r->piloto?->apellido,
            'escuderia'              => $r->escuderia?->nombre,
            'posicion_salida'        => $r->posicion_salida,
            'posicion_final'         => $r->posicion_final,
            'posicion_clasificacion' => $r->posicion_clasificacion,
            'estado'                 => $r->estado,
            'vuelta_rapida'          => $r->vuelta_rapida,
            'piloto_del_dia'         => $r->piloto_del_dia,
            'penalizacion_grid'      => (bool) $r->penalizacion_grid,
            'penalizacion_tiempo'    => (bool) $r->penalizacion_tiempo,
            'puntos_carrera'         => $r->puntos_carrera,
            'puntos_velocidad'       => $r->puntos_velocidad,
            'puntos_fantasy'         => $r->puntos_fantasy,
        ]);

        // Agrupado por escudería para ver pts de escudería y coche
        $porEscuderia = $resultados->groupBy('escuderia_id')->map(function ($grupo) use ($reglas) {
            $sumaCarrera = $grupo->sum('puntos_carrera');
            $sumaQuali   = $grupo->sum(function ($r) use ($reglas) {
                $ev = 'QUALI_P' . $r->posicion_clasificacion;
                return ($r->posicion_clasificacion && isset($reglas[$ev])) ? $reglas[$ev]->puntos : 0;
            });

            return [
                'escuderia'     => $grupo->first()->escuderia?->nombre,
                'suma_carrera'  => $sumaCarrera,
                'suma_qualy'    => $sumaQuali,
                'pts_escuderia' => (int) round($sumaCarrera / 2),
                'pts_coche'     => (int) round($sumaQuali / 2),
            ];
        })->sortBy('escuderia')->values();

        return response()->json([
            'carrera'       => $carrera->nombre,
            'estado'        => $carrera->estado,
            'resultados'    => $filas,
            'por_escuderia' => $porEscuderia,
        ]);
    }

    public function panelControl(): JsonResponse
    {
        // Usar la temporada más reciente que haya en la BD
        $temporada = Carrera::max('temporada') ?? now()->year;

        return response()->json([
            'estadisticas' => [
                'total_usuarios'      => User::count(),
                'total_ligas'         => Liga::count(),
                'total_equipos'       => EquipoFantasy::count(),
                'carreras_puntuadas'  => Carrera::where('temporada', $temporada)->where('estado', 'scored')->count(),
                'carreras_pendientes' => Carrera::where('temporada', $temporada)->where('estado', 'upcoming')->count(),
            ],
            'carreras_recientes' => Carrera::with('circuito')
                ->where('temporada', $temporada)
                ->where('fecha', '<=', now()->toDateString())
                ->orderByDesc('fecha')
                ->limit(5)
                ->get(),
        ]);
    }
}
