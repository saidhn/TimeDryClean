<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        dd("Test inside redirect if authenticated");
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if ($guard === 'client') {
                    return redirect()->route('client.dashboard');
                } elseif ($guard === 'driver') {
                    return redirect()->route('driver.dashboard');
                } elseif ($guard === 'employee') {
                    return redirect()->route('employee.dashboard');
                } elseif ($guard === 'admin') {
                    return redirect()->route('admin.dashboard');
                } else {
                    return redirect()->route('/'); // Default route
                }
            }
        }

        return $next($request);
    }
}
