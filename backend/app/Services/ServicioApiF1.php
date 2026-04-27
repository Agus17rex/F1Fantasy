<?php

namespace App\Services;

use App\Models\Circuito;
use App\Models\DirectorEquipo;
use App\Models\Escuderia;
use App\Models\Piloto;
use App\Models\Carrera;
use App\Models\ResultadoCarrera;
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

    /**
     * Realiza una petición GET a la API.
     * En local desactivamos la verificación SSL porque Windows
     * no siempre tiene el certificado de la API instalado.
     */
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
                    'code'          => $datosPiloto['code'] ?? null,
                    'number'        => $datosPiloto['permanentNumber'] ?? null,
                    'first_name'    => $datosPiloto['givenName'],
                    'last_name'     => $datosPiloto['familyName'],
                    'nationality'   => $datosPiloto['nationality'] ?? null,
                    'date_of_birth' => $datosPiloto['dateOfBirth'] ?? null,
                    'is_active'     => true,
                ]
            );
            $sincronizados++;
        }

        // Vinculamos cada piloto con su escudería usando la clasificación de pilotos,
        // que sí incluye a qué constructor pertenece cada uno
        $this->vincularPilotosEscuderias($temporada);

        // Recalculamos precios basados en la clasificación actual
        $this->actualizarPreciosPilotos($temporada);

        return $sincronizados;
    }

    /**
     * Obtiene la clasificación de pilotos y usa esa información para asignar
     * el constructor_id correcto a cada piloto en nuestra base de datos.
     * Los pilotos que NO aparezcan en la clasificación se marcan como reservas.
     */
    private function vincularPilotosEscuderias(?int $temporada = null): void
    {
        $clasificacion = $this->obtenerClasificacionPilotos($temporada);

        // IDs de pilotos que aparecen en la clasificación (son titulares)
        $pilotosTitulares = collect($clasificacion)->pluck('Driver.driverId')->all();

        foreach ($clasificacion as $entrada) {
            $piloto    = Piloto::where('api_id', $entrada['Driver']['driverId'])->first();
            $escuderia = Escuderia::where('api_id', $entrada['Constructors'][0]['constructorId'] ?? null)->first();

            if ($piloto && $escuderia) {
                $piloto->update([
                    'constructor_id' => $escuderia->id,
                    'es_reserva'     => false,
                ]);
            }
        }

        // Los pilotos que NO están en la clasificación no son titulares → desactivar
        Piloto::whereNotIn('api_id', $pilotosTitulares)
            ->update(['is_active' => false, 'es_reserva' => true]);
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
                    'name'        => $datosEscuderia['name'],
                    'nationality' => $datosEscuderia['nationality'] ?? null,
                    'is_active'   => true,
                ]
            );
            $sincronizados++;
        }

        // Recalculamos precios de escuderías y directores
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
                    'name'     => $datosCarrera['Circuit']['circuitName'],
                    'location' => $datosCarrera['Circuit']['Location']['locality'] ?? null,
                    'country'  => $datosCarrera['Circuit']['Location']['country'] ?? null,
                    'lat'      => $datosCarrera['Circuit']['Location']['lat'] ?? null,
                    'lng'      => $datosCarrera['Circuit']['Location']['long'] ?? null,
                ]
            );

            Carrera::updateOrCreate(
                ['api_id' => $datosCarrera['season'] . '_' . $datosCarrera['round']],
                [
                    'season'     => (int) $datosCarrera['season'],
                    'round'      => (int) $datosCarrera['round'],
                    'name'       => $datosCarrera['raceName'],
                    'circuit_id' => $circuito->id,
                    'date'       => $datosCarrera['date'],
                    // La API devuelve la hora con 'Z' al final (ej: "15:00:00Z"), la quitamos
                    'time'       => isset($datosCarrera['time']) ? rtrim($datosCarrera['time'], 'Z') : null,
                    'status'     => 'upcoming',
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
        $resultados    = $this->obtenerResultadosCarrera($carrera->season, $carrera->round);
        $clasificacion = $this->obtenerResultadosClasificacion($carrera->season, $carrera->round);

        // Mapa de driverId => posición en clasificación para buscarlo rápido
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
                ['race_id' => $carrera->id, 'driver_id' => $piloto->id],
                [
                    'constructor_id'      => $escuderia->id,
                    'grid_position'       => $resultado['grid'] ?? null,
                    'finish_position'     => $resultado['position'] ?? null,
                    'qualifying_position' => $posicionesClasificacion[$resultado['Driver']['driverId']] ?? null,
                    'status'              => $resultado['status'] ?? 'Unknown',
                    'points_official'     => (int) ($resultado['points'] ?? 0),
                    'fastest_lap'         => isset($resultado['FastestLap']['rank']) && $resultado['FastestLap']['rank'] == 1,
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

    /**
     * Recalcula los precios de pilotos, escuderías y directores en función de
     * la clasificación actual del campeonato.
     *
     * Escala lineal:  posición 1  → 80 000 000 €
     *                 última pos. → 10 000 000 €
     */
    public function actualizarPrecios(?int $temporada = null): void
    {
        $this->actualizarPreciosPilotos($temporada);
        $this->actualizarPreciosEscuderias($temporada);
        // Los directores se actualizan dentro de actualizarPreciosEscuderias
        // porque su precio se calcula a partir del de su escudería
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
                  ->update(['price' => $precio]);
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

            $escuderia->update(['price' => $precio]);

            // El director de esta escudería recibe un precio = 80 % del de la escudería
            // (mismo orden en clasificación, rango ligeramente inferior)
            DirectorEquipo::where('constructor_id', $escuderia->id)
                          ->update(['price' => (int) round($precio * 0.8)]);
        }

        Log::info("Precios de escuderías y directores actualizados ({$total} equipos)");
    }

    /**
     * Escala lineal entre $minM y $maxM millones según la posición en la clasificación.
     *
     * Ejemplo con 20 pilotos:
     *   posición  1 → 80 M
     *   posición 20 → 10 M
     *   posición 10 → ~46 M
     */
    private function escalarPrecio(int $posicion, int $total, int $minM = 10, int $maxM = 80): int
    {
        if ($total <= 1) {
            return (int) round(($minM + $maxM) / 2 * 1_000_000);
        }

        // fraccion = 1.0 cuando posicion=1, fraccion = 0.0 cuando posicion=$total
        $fraccion = ($total - $posicion) / ($total - 1);

        return (int) round(($minM + $fraccion * ($maxM - $minM)) * 1_000_000);
    }
}
