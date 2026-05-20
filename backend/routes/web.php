<?php

use Illuminate\Support\Facades\Route;

/*
 * Catch-all SPA — devuelve el index.html compilado de Vue para cualquier ruta
 * que no sea /api/* ni un fichero estático existente.
 * Necesario porque Vue Router usa createWebHistory (sin #).
 */
Route::get('/{path?}', function () {
    $index = public_path('index.html');

    abort_unless(
        file_exists($index),
        404,
        'Frontend no compilado. Ejecuta: cd frontend && npm run build'
    );

    return response()->file($index);
})->where('path', '^(?!api(/|$)).*');
