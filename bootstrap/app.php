<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RoleMiddleware;
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
    ->withMiddleware(function (Middleware $middleware) {
        // The app runs behind a reverse proxy that terminates TLS (see README).
        // Trust forwarded headers so URL/scheme generation and rate-limiting
        // by IP work correctly. Narrow this to your proxy's IP if it is fixed.
        $middleware->trustProxies(at: '*');

        // Route middleware aliases used throughout the app.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);

        // Every authenticated web request must belong to an enabled account.
        $middleware->web(append: [
            EnsureUserIsActive::class,
        ]);

        // The API is token-only (Sanctum bearer tokens); no stateful/session
        // middleware is added here on purpose.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Return JSON errors for API routes instead of HTML redirects.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })
    ->create();
