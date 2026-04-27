<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\EquipoFantasy;
use App\Models\Liga;
use App\Models\User;
use App\Services\ServicioApiF1;
use App\Services\ServicioPuntuacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ControladorAdminCarrera extends Controller
{
    public function __construct(
        private ServicioApiF1      $servicioApi,
        private ServicioPuntuacion $servicioPuntuacion
    ) {}

    /**
     * Sincroniza todos los datos desde la API de F1 (pilotos, escuderías y carreras)
     */
    public function sincronizarDatosF1(Request $request): JsonResponse
    {
        $temporada = $request->get('season', date('Y'));

        // El orden importa: primero escuderías, luego pilotos (para poder vincularlos)
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

    /**
     * Sincroniza los resultados de una carrera específica
     */
    public function sincronizarResultados(Carrera $carrera): JsonResponse
    {
        $sincronizados = $this->servicioApi->sincronizarResultadosCarrera($carrera);

        return response()->json([
            'message' => "Resultados sincronizados: {$sincronizados} registros",
            'carrera' => $carrera->load('resultados'),
        ]);
    }

    /**
     * Calcula los puntos fantasy de una carrera
     */
    public function puntuarCarrera(Carrera $carrera): JsonResponse
    {
        if ($carrera->resultados->isEmpty()) {
            return response()->json(['message' => 'No hay resultados para esta carrera'], 422);
        }

        if ($carrera->status === 'scored') {
            return response()->json(['message' => 'Esta carrera ya ha sido puntuada'], 422);
        }

        $this->servicioPuntuacion->procesarPuntosCarrera($carrera);

        return response()->json([
            'message' => "Puntos calculados para: {$carrera->name}",
            'carrera' => $carrera->fresh(),
        ]);
    }

    /**
     * Lista las carreras de la temporada actual con su estado
     */
    public function carreras(): JsonResponse
    {
        $carreras = Carrera::with(['circuito', 'resultados'])
            ->withCount('resultados')
            ->where('season', now()->year)
            ->orderBy('date')
            ->get();

        return response()->json($carreras);
    }

    /**
     * Recalcula los precios de pilotos, escuderías y directores
     * usando la clasificación actual del campeonato (escala 10 M – 80 M).
     */
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

    /**
     * Panel de control con estadísticas generales
     */
    public function panelControl(): JsonResponse
    {
        return response()->json([
            'estadisticas' => [
                'total_usuarios'      => User::count(),
                'total_ligas'         => Liga::count(),
                'total_equipos'       => EquipoFantasy::count(),
                'carreras_puntuadas'  => Carrera::where('season', now()->year)->where('status', 'scored')->count(),
                'carreras_pendientes' => Carrera::where('season', now()->year)->where('status', 'upcoming')->count(),
            ],
            'carreras_recientes' => Carrera::with('circuito')
                ->where('season', now()->year)
                ->orderByDesc('date')
                ->limit(5)
                ->get(),
        ]);
    }
}
