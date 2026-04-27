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
            return response()->json(['message' => 'Esta carrera ya ha sido puntuada'], 422);
        }

        $this->servicioPuntuacion->procesarPuntosCarrera($carrera);

        return response()->json([
            'message' => "Puntos calculados para: {$carrera->nombre}",
            'carrera' => $carrera->fresh(),
        ]);
    }

    public function carreras(): JsonResponse
    {
        $carreras = Carrera::with(['circuito', 'resultados'])
            ->withCount('resultados')
            ->where('temporada', now()->year)
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

    public function panelControl(): JsonResponse
    {
        return response()->json([
            'estadisticas' => [
                'total_usuarios'      => User::count(),
                'total_ligas'         => Liga::count(),
                'total_equipos'       => EquipoFantasy::count(),
                'carreras_puntuadas'  => Carrera::where('temporada', now()->year)->where('estado', 'scored')->count(),
                'carreras_pendientes' => Carrera::where('temporada', now()->year)->where('estado', 'upcoming')->count(),
            ],
            'carreras_recientes' => Carrera::with('circuito')
                ->where('temporada', now()->year)
                ->orderByDesc('fecha')
                ->limit(5)
                ->get(),
        ]);
    }
}
