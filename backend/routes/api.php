<?php

use App\Http\Controllers\Admin\ControladorAdminCarrera;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ControladorF1;
use App\Http\Controllers\Api\ControladorLiga;
use App\Http\Controllers\Api\ControladorEquipoFantasy;
use Illuminate\Support\Facades\Route;

// ─── Auth (público) ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// ─── Datos F1 (público) ───────────────────────────────────────────────────────
Route::prefix('f1')->group(function () {
    Route::get('/pilotos',                  [ControladorF1::class, 'pilotos']);
    Route::get('/pilotos/{piloto}',         [ControladorF1::class, 'piloto']);
    Route::get('/escuderias',               [ControladorF1::class, 'escuderias']);
    Route::get('/escuderias/{escuderia}',   [ControladorF1::class, 'escuderia']);
    Route::get('/carreras',                 [ControladorF1::class, 'carreras']);
    Route::get('/carreras/proxima',         [ControladorF1::class, 'proximaCarrera']);
    Route::get('/carreras/{carrera}',       [ControladorF1::class, 'carrera']);
    Route::get('/clasificacion/pilotos',    [ControladorF1::class, 'clasificacionPilotos']);
    Route::get('/clasificacion/escuderias', [ControladorF1::class, 'clasificacionEscuderias']);
    Route::get('/coches',                            [ControladorF1::class, 'coches']);
    Route::get('/reglas',                            [ControladorF1::class, 'reglas']);
    Route::get('/fantasy-ranking',                   [ControladorF1::class, 'rankingFantasy']);
    Route::get('/puntuacion-temporada',              [ControladorF1::class, 'puntuacionTemporada']);
    Route::get('/carreras/{carrera}/puntuacion',     [ControladorF1::class, 'puntuacionCarrera']);
});

// ─── Rutas autenticadas ───────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout',  [AuthController::class, 'logout']);
    Route::get('/auth/me',       [AuthController::class, 'me']);
    Route::put('/auth/perfil',   [AuthController::class, 'actualizarPerfil']);

    // Ligas
    Route::post('/ligas/unirse', [ControladorLiga::class, 'join']);
    Route::apiResource('ligas', ControladorLiga::class)->except(['update']);

    // Mercado y equipo dentro de una liga
    Route::prefix('ligas/{liga}')->group(function () {
        // Datos de la liga
        Route::get('/mercado',  [ControladorLiga::class, 'mercado']);
        Route::get('/equipo',                    [ControladorEquipoFantasy::class, 'show']);
        Route::get('/equipo/usuario/{userId}',   [ControladorEquipoFantasy::class, 'showDeUsuario']);
        Route::get('/puntuaciones',              [ControladorEquipoFantasy::class, 'puntuaciones']);

        // Pilotos
        Route::post('/equipo/pilotos',            [ControladorEquipoFantasy::class, 'comprarPiloto']);
        Route::delete('/equipo/pilotos/{piloto}', [ControladorEquipoFantasy::class, 'venderPiloto']);

        // Coche
        Route::post('/equipo/coche',           [ControladorEquipoFantasy::class, 'comprarCoche']);
        Route::delete('/equipo/coche/{coche}', [ControladorEquipoFantasy::class, 'venderCoche']);

        // Escudería
        Route::post('/equipo/escuderia',               [ControladorEquipoFantasy::class, 'comprarEscuderia']);
        Route::delete('/equipo/escuderia/{escuderia}', [ControladorEquipoFantasy::class, 'venderEscuderia']);

        // Robar (transfer/steal)
        Route::post('/equipo/robar/pilotos',   [ControladorEquipoFantasy::class, 'robarPiloto']);
        Route::post('/equipo/robar/escuderia', [ControladorEquipoFantasy::class, 'robarEscuderia']);
        Route::post('/equipo/robar/coche',     [ControladorEquipoFantasy::class, 'robarCoche']);
    });

    // ─── Admin ───────────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/panel',                                       [ControladorAdminCarrera::class, 'panelControl']);
        Route::get('/carreras',                                    [ControladorAdminCarrera::class, 'carreras']);
        Route::post('/sincronizar',                                [ControladorAdminCarrera::class, 'sincronizarDatosF1']);
        Route::post('/carreras/{carrera}/sincronizar',             [ControladorAdminCarrera::class, 'sincronizarResultados']);
        Route::post('/carreras/{carrera}/puntuar',                 [ControladorAdminCarrera::class, 'puntuarCarrera']);
        Route::get('/carreras/{carrera}/puntuacion',               [ControladorAdminCarrera::class, 'puntuacionCarrera']);
        Route::patch('/resultados/{resultado}/penalizaciones',     [ControladorAdminCarrera::class, 'actualizarPenalizaciones']);
        Route::post('/recalcular',                                 [ControladorAdminCarrera::class, 'recalcularTodo']);
        Route::post('/precios',                                    [ControladorAdminCarrera::class, 'actualizarPrecios']);
    });
});
