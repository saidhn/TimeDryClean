<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('client/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling configuration
    })->create();

// Register Route Middleware HERE (Correct way in Laravel 11)
// $app->router->aliasMiddleware('auth', \App\Http\Middleware\Authenticate::class);
// $app->router->aliasMiddleware('guest', \App\Http\Middleware\RedirectIfAuthenticated::class);

return $app;
