<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\DriverAuthController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientOrderController;
use App\Http\Controllers\Client\ClientProfileController;
use App\Http\Controllers\Client\ClientSettingsController;
use Illuminate\Support\Facades\Route;

//show login form for homepage
Route::get('/', [ClientAuthController::class, 'showLoginForm'])->name("home");
//-----------------------------------------------------------------------------------
//-------------------------------- Users Auth Routes --------------------------------
//-----------------------------------------------------------------------------------
Route::prefix('client')->group(function () {
    Route::get('/login', [ClientAuthController::class, 'showLoginForm'])->name('client.login'); // Define the login route
    Route::post('/login', [ClientAuthController::class, 'login'])->name('client.login.post');
    Route::get('/register', [ClientAuthController::class, 'showRegistrationForm'])->name('client.register');
    Route::post('/register', [ClientAuthController::class, 'register'])->name('client.register.post');
    Route::post('/logout', [ClientAuthController::class, 'logout'])->name('client.logout');
});

// Repeat this pattern for driver, employee, and admin routes
Route::prefix('driver')->group(function () {
    Route::get('/login', [DriverAuthController::class, 'showLoginForm'])->name('driver.login');
    Route::post('/login', [DriverAuthController::class, 'login'])->name('driver.login.post');
    Route::get('/register', [DriverAuthController::class, 'showRegistrationForm'])->name('driver.register');
    Route::post('/register', [DriverAuthController::class, 'register'])->name('driver.register.post');
    Route::post('/logout', [DriverAuthController::class, 'logout'])->name('driver.logout');
    Route::get('/dashboard', function () {
        return "driver Dashboard";
    })->middleware('auth:driver');
});

Route::prefix('employee')->group(function () {
    Route::get('/login', [EmployeeAuthController::class, 'showLoginForm'])->name('employee.login');
    Route::post('/login', [EmployeeAuthController::class, 'login'])->name('employee.login.post');
    Route::get('/register', [EmployeeAuthController::class, 'showRegistrationForm'])->name('employee.register');
    Route::post('/register', [EmployeeAuthController::class, 'register'])->name('driver.register.post');
    Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('employee.logout');
    Route::get('/dashboard', function () {
        return "employee Dashboard";
    })->middleware('auth:employee');
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::get('/register', [AdminAuthController::class, 'showRegistrationForm'])->name('admin.register');
    Route::post('/register', [AdminAuthController::class, 'register'])->name('driver.register.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    Route::get('/dashboard', function () {
        return "admin Dashboard";
    })->middleware('auth:admin');
});

//-----------------------------------------------------------------------------------
//--------------------------------    Client Routes    ------------------------------
//-----------------------------------------------------------------------------------
Route::middleware(['auth:client'])->group(function () {
    Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
});
Route::prefix('client')->middleware('auth:client')->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');

    Route::resource('orders', ClientOrderController::class)->names([ // Resourceful routing with names
        'index' => 'client.orders.index',
        'create' => 'client.orders.create',
        'store' => 'client.orders.store',
        'show' => 'client.orders.show',
        'edit' => 'client.orders.edit',
        'update' => 'client.orders.update',
        'destroy' => 'client.orders.destroy',
    ]);

    Route::get('/profile/edit', [ClientProfileController::class, 'edit'])->name('client.profile.edit');
    Route::put('/profile', [ClientProfileController::class, 'update'])->name('client.profile.update');

    Route::get('/settings', [ClientSettingsController::class, 'index'])->name('client.settings');
});
//-----------------------------------------------------------------------------------
//--------------------------------        END        --------------------------------
//-----------------------------------------------------------------------------------