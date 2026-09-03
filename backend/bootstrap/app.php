<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            // API uses Sanctum bearer tokens; web sessions are CSRF-protected.
        ]);

        // Spatie permission middleware aliases (Laravel 11 slim bootstrap does
        // not register these automatically for us).
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

// Application code lives in src/ (composer autoload maps App\ => src/), but
// Laravel defaults path() to <base>/app. Point the app path at src/ so the
// application namespace resolves and getNamespace() succeeds.
$app->useAppPath(__DIR__.'/../src');

return $app;
