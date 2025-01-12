<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            if ($request->routeIs('client.*')) {
                return route('client.login');
            } elseif ($request->routeIs('driver.*')) {
                return route('driver.login');
            } elseif ($request->routeIs('employee.*')) {
                return route('employee.login');
            } elseif ($request->routeIs('admin.*')) {
                return route('admin.login');
            }
            // Important: Throw an exception if no guard matches
            throw new \Exception('No login route defined for the current guard.');
            // Or you could redirect to a general error page:
            // return '/unauthorized'; // You would need to create this route/view
        }

        return null;
    }
}
