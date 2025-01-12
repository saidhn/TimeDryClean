<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const HOME = '/home'; // Default home route
    public const CLIENT_HOME = '/client/dashboard';
    public const DRIVER_HOME = '/driver/dashboard';
    public const EMPLOYEE_HOME = '/employee/dashboard';
    public const ADMIN_HOME = '/admin/dashboard';
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
