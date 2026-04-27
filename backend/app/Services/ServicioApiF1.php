<?php

namespace App\Services;

use App\Models\Circuito;
use App\Models\DirectorEquipo;
use App\Models\Escuderia;
use App\Models\Piloto;
use App\Models\Carrera;
use App\Models\ResultadoCarrera;
use App\Support\Traducciones;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServicioApiF1
{
    private string $urlBase;
    private int $temporada;

    public function __construct()
    {
        $this->urlBase   = config('services.f1_api.base_url', 'https://api.jolpi.ca/ergast/f1');
        $this->temporada = config('services.f1_api.season', date('Y'));
    }

    private function peticion(string $url): \Illuminate\Http\Client\Response
    {
        $cliente = app()->isLocal()
            ? Http::withoutVerifying()->timeout(15)
            : Http::timeout(15);

        return $cliente->get($url);
    }

    // ─── Pilotos ──────────────────────────────────────────────────────────────

    public function obtenerPilotos(?int $temporada = null): array
    {
        $anio     = $temporada ?? $this->temporada;
        $respuesta = $this->peticion("{$this->urlBase}/{$anio}/drivers.json");

        if ($respuesta->failed()) {
            Log::error('Error en API F1 al obtener pilotos', ['estado' => $respuesta->status()]);
            return [];
        }

        return $respuesta->json('MRData.DriverTable.Drivers', []);
    }

    public function sincronizarPilotos(?int $temporada = null): int
    {
        $pilotos       = $this->obtenerPilotos($temporada);
        $sincronizados = 0;

        foreach ($pilotos as $datosPiloto) {
            Piloto::updateOrCreate(
                ['api_id' => $datosPiloto['driverId']],
                [
                    'codigo'           => $datosPiloto['code'] ?? null,
                    'numero'           => $datosPiloto['permanentNumber'] ?? null,
                    'nombre'           => $datosPiloto['givenName'],
                    'apellido'         => $datosPiloto['familyName'],
                    'nacionalidad'     => Traducciones::nacionalidad($datosPiloto['nationality'] ?? null),
                    'fecha_nacimiento' => $datosPiloto['dateOfBirth'] ?? null,
                    'activo'           => true,
                ]
            );
            $sincronizados++;
        }

        $this->vincularPilotosEscuderias($temporada);
        $this->actualizarPreciosPilotos($temporada);

        return $sincronizados;
    }

    /**
     * Asigna a cada piloto su escudería actual (a partir de la clasificación).
     */
    private function vincularPilotosEscuderias(?int $temporada = null): void
    {
        $clasificacion = $this->obtenerClasificacionPilotos($temporada);

        $pilotosTitulares = collect($clasificacion)->pluck('Driver.driverId')->all();

        foreach ($clasificacion as $entrada) {
            $piloto    = Piloto::where('api_id', $entrada['Driver']['driverId'])->first();
            $escuderia = Escuderia::where('api_id', $entrada['Constructors'][0]['constructorId'] ?? null)->first();

            if ($piloto && $escuderia) {
                $piloto->update([
                    'escuderia_id' => $escuderia->id,
                    'es_reserva'   => false,
                ]);
            }
        }

        Piloto::whereNotIn('api_id', $pilotosTitulares)
            ->update(['activo' => false, 'es_reserva' => true]);
    }

    // ─── Escuderías ───────────────────────────────────────────────────────────

    public function obtenerEscuderias(?int $temporada = null): array
    {
        $anio      = $temporada ?? $this->temporada;
        $respuesta = $this->peticion("{$this->urlBase}/{$anio}/constructors.json");

        if ($respuesta->failed()) {
            Log::error('Error en API F1 al obtener escuderías', ['estado' => $respuesta->status()]);
            return [];
        }

        return $respuesta->json('MRData.ConstructorTable.Constructors', []);
    }

    public function sincronizarEscuderias(?int $temporada = null): int
    {
        $escuderias    = $this->obtenerEscuderias($temporada);
        $sincronizados = 0;

        foreach ($escuderias as $datosEscuderia) {
            Escuderia::updateOrCreate(
                ['api_id' => $datosEscuderia['constructorId']],
                [
                    'nombre'       => $datosEscuderia['name'],
                    'nacionalidad' => Traducciones::nacionalidad($datosEscuderia['nationality'] ?? null),
                    'activa'       => true,
                ]
            );
            $sincronizados++;
        }

        $this->actualizarPreciosEscuderias($temporada);

        return $sincronizados;
    }

    // ─── Carreras / Calendario ────────────────────────────────────────────────

    public function obtenerCarreras(?int $temporada = null): array
    {
        $anio      = $temporada ?? $this->temporada;
        $respuesta = $this->peticion("{$this->urlBase}/{$anio}.json");

        if ($respuesta->failed()) {
            Log::error('Error en API F1 al obtener carreras', ['estado' => $respuesta->status()]);
            return [];
        }

        return $respuesta->json('MRData.RaceTable.Races', []);
    }

    public function sincronizarCarreras(?int $temporada = null): int
    {
        $carreras      = $this->obtenerCarreras($temporada);
        $sincronizados = 0;

        foreach ($carreras as $datosCarrera) {
            $circuito = Circuito::updateOrCreate(
                ['api_id' => $datosCarrera['Circuit']['circuitId']],
                [
                    'nombre'    => $datosCarrera['Circuit']['circuitName'],
                    'ubicacion' => $datosCarrera['Circuit']['Location']['locality'] ?? null,
                    'pais'      => $datosCarrera['Circuit']['Location']['country'] ?? null,
                    'lat'       => $datosCarrera['Circuit']['Location']['lat'] ?? null,
                    'lng'       => $datosCarrera['Circuit']['Location']['long'] ?? null,
                ]
            );

            Carrera::updateOrCreate(
                ['api_id' => $datosCarrera['season'] . '_' . $datosCarrera['round']],
                [
                    'temporada'   => (int) $datosCarrera['season'],
                    'ronda'       => (int) $datosCarrera['round'],
                    'nombre'      => $datosCarrera['raceName'],
                    'circuito_id' => $circuito->id,
                    'fecha'       => $datosCarrera['date'],
                    'hora'        => isset($datosCarrera['time']) ? rtrim($datosCarrera['time'], 'Z') : null,
                    'estado'      => 'upcoming',
                ]
            );
            $sincronizados++;
        }

        return $sincronizados;
    }

    // ─── Resultados de carrera ────────────────────────────────────────────────

    public function obtenerResultadosCarrera(int $temporada, int $ronda): array
    {
        $respuesta = $this->peticion("{$this->urlBase}/{$temporada}/{$ronda}/results.json");

        if ($respuesta->failed()) {
            return [];
        }

        return $respuesta->json('MRData.RaceTable.Races.0.Results', []);
    }

    public function obtenerResultadosClasificacion(int $temporada, int $ronda): array
    {
        $respuesta = $this->peticion("{$this->urlBase}/{$temporada}/{$ronda}/qualifying.json");

        if ($respuesta->failed()) {
            return [];
        }

        return $respuesta->json('MRData.RaceTable.Races.0.QualifyingResults', []);
    }

    public function sincronizarResultadosCarrera(Carrera $carrera): int
    {
        $resultados    = $this->obtenerResultadosCarrera($carrera->temporada, $carrera->ronda);
        $clasificacion = $this->obtenerResultadosClasificacion($carrera->temporada, $carrera->ronda);

        $posicionesClasificacion = [];
        foreach ($clasificacion as $entrada) {
            $idPiloto = $entrada['Driver']['driverId'];
            $posicionesClasificacion[$idPiloto] = (int) $entrada['position'];
        }

        $sincronizados = 0;

        foreach ($resultados as $resultado) {
            $piloto    = Piloto::where('api_id', $resultado['Driver']['driverId'])->first();
            $escuderia = Escuderia::where('api_id', $resultado['Constructor']['constructorId'])->first();

            if (!$piloto || !$escuderia) {
                continue;
            }

            ResultadoCarrera::updateOrCreate(
                ['carrera_id' => $carrera->id, 'piloto_id' => $piloto->id],
                [
                    'escuderia_id'           => $escuderia->id,
                    'posicion_salida'        => $resultado['grid'] ?? null,
                    'posicion_final'         => $resultado['position'] ?? null,
                    'posicion_clasificacion' => $posicionesClasificacion[$resultado['Driver']['driverId']] ?? null,
                    'estado'                 => Traducciones::statusResultado($resultado['status'] ?? 'Unknown'),
                    'puntos_oficiales'       => (int) ($resultado['points'] ?? 0),
                    'vuelta_rapida'          => isset($resultado['FastestLap']['rank']) && $resultado['FastestLap']['rank'] == 1,
                ]
            );
            $sincronizados++;
        }

        return $sincronizados;
    }

    // ─── Clasificaciones ──────────────────────────────────────────────────────

    public function obtenerClasificacionPilotos(?int $temporada = null): array
    {
        $anio      = $temporada ?? $this->temporada;
        $respuesta = $this->peticion("{$this->urlBase}/{$anio}/driverStandings.json");

        if ($respuesta->failed()) {
            return [];
        }

        return $respuesta->json('MRData.StandingsTable.StandingsLists.0.DriverStandings', []);
    }

    public function obtenerClasificacionEscuderias(?int $temporada = null): array
    {
        $anio      = $temporada ?? $this->temporada;
        $respuesta = $this->peticion("{$this->urlBase}/{$anio}/constructorStandings.json");

        if ($respuesta->failed()) {
            return [];
        }

        return $respuesta->json('MRData.StandingsTable.StandingsLists.0.ConstructorStandings', []);
    }

    // ─── Precios dinámicos ────────────────────────────────────────────────────

    public function actualizarPrecios(?int $temporada = null): void
    {
        $this->actualizarPreciosPilotos($temporada);
        $this->actualizarPreciosEscuderias($temporada);
    }

    private function actualizarPreciosPilotos(?int $temporada = null): void
    {
        $clasificacion = $this->obtenerClasificacionPilotos($temporada);

        if (empty($clasificacion)) {
            Log::warning('actualizarPreciosPilotos: clasificación vacía, no se actualizan precios');
            return;
        }

        $total = count($clasificacion);

        foreach ($clasificacion as $entrada) {
            $posicion = (int) $entrada['position'];
            $precio   = $this->escalarPrecio($posicion, $total);

            Piloto::where('api_id', $entrada['Driver']['driverId'])
                  ->update(['precio' => $precio]);
        }

        Log::info("Precios de pilotos actualizados ({$total} titulares)");
    }

    private function actualizarPreciosEscuderias(?int $temporada = null): void
    {
        $clasificacion = $this->obtenerClasificacionEscuderias($temporada);

        if (empty($clasificacion)) {
            Log::warning('actualizarPreciosEscuderias: clasificación vacía, no se actualizan precios');
            return;
        }

        $total = count($clasificacion);

        foreach ($clasificacion as $entrada) {
            $posicion  = (int) $entrada['position'];
            $precio    = $this->escalarPrecio($posicion, $total);
            $apiId     = $entrada['Constructor']['constructorId'];

            $escuderia = Escuderia::where('api_id', $apiId)->first();

            if (!$escuderia) {
                continue;
            }

            $escuderia->update(['precio' => $precio]);

            DirectorEquipo::where('escuderia_id', $escuderia->id)
                          ->update(['precio' => (int) round($precio * 0.8)]);
        }

        Log::info("Precios de escuderías y directores actualizados ({$total} equipos)");
    }

    private function escalarPrecio(int $posicion, int $total, int $minM = 10, int $maxM = 80): int
    {
        if ($total <= 1) {
            return (int) round(($minM + $maxM) / 2 * 1_000_000);
        }

        $fraccion = ($total - $posicion) / ($total - 1);

        return (int) round(($minM + $fraccion * ($maxM - $minM)) * 1_000_000);
    }
}
