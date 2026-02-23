<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('client/login');
        $middleware->validateCsrfTokens(except: [
            'payment/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Illuminate\Database\QueryException $e, $request) {
            $pdo = $e->getPrevious();
            $isConnectionError = $pdo instanceof \PDOException
                && in_array($pdo->getCode(), ['HY000', '2002', '1045', '1049', '08S01']);

            if ($isConnectionError) {
                $detail = app()->isProduction()
                    ? null
                    : $pdo->getMessage();

                return response()->view('errors.database', [
                    'detail' => $detail,
                ], 503);
            }
        });
    })->create();

// Register Route Middleware HERE (Correct way in Laravel 11)
// $app->router->aliasMiddleware('auth', \App\Http\Middleware\Authenticate::class);
// $app->router->aliasMiddleware('guest', \App\Http\Middleware\RedirectIfAuthenticated::class);

$app->router->aliasMiddleware('set_locale', \App\Http\Middleware\SetLocale::class);

return $app;
