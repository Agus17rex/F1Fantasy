<?php

use App\Http\Middleware\EsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Devolver JSON para todos los errores en rutas /api
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));
    })
    ->create();
