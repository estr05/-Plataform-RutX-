<?php

use App\Http\Middleware\AuthSession;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Headers de seguridad globales (guidelines §1.6)
        $middleware->append(SecurityHeaders::class);

        // Alias de autorización (guidelines §1.3):
        // auth.session: exige JWT web en sesión cifrada.
        // role: primera mitad de la autorización doble (validación local de roles).
        $middleware->alias([
            'auth.session' => AuthSession::class,
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
