<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sama seperti SCOPE: masukkan ke grup api
        $middleware->api(prepend: [
            \App\Http\Middleware\CorsMiddleware::class,
        ]);

        // Register Sphere SSO token verification middleware alias
        $middleware->alias([
            'sphere.auth' => \App\Http\Middleware\VerifySphereToken::class,
            'cors'        => \App\Http\Middleware\CorsMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
