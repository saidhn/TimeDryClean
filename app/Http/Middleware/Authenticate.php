<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    public function __construct()
    {
        Log::info('Authenticate Middleware Initialized');
    }

    protected function redirectTo(Request $request): ?string
    {
        dd("should redirect to client.login");
        Log::info('Authenticate Middleware - Request URL: ' . $request->fullUrl());
        Log::info('Authenticate Middleware - Route Name: ' . ($request->route() ? $request->route()->getName() : 'No route'));

        if (! $request->expectsJson()) {
            if ($request->routeIs('client.*')) {
                Log::info('Redirecting to client.login');
                return route('client.login'); // Correct redirection for client guard
            } elseif ($request->routeIs('driver.*')) {
                Log::info('Redirecting to driver.login');
                return route('driver.login');
            } elseif ($request->routeIs('employee.*')) {
                Log::info('Redirecting to employee.login');
                return route('employee.login');
            } elseif ($request->routeIs('admin.*')) {
                Log::info('Redirecting to admin.login');
                return route('admin.login');
            }
            // VERY IMPORTANT: If NO guard matches, THROW AN EXCEPTION or redirect to a general error page.
            Log::error('Authenticate Middleware - No matching guard found! URL: ' . $request->fullUrl());
            throw new \Exception('No login route defined for the current guard. URL: ' . $request->fullUrl());
            // Or: return '/unauthorized'; // Create this route/view if you choose this option
        }

        return null;
    }
}
