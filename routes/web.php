<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\DriverAuthController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
//-----------------------------------------------------------------------------------
//-------------------------------- Users Auth Routes --------------------------------
//-----------------------------------------------------------------------------------
Route::prefix('client')->group(function () {
    Route::get('/login', [ClientAuthController::class, 'showLoginForm'])->name('client.login');
    Route::post('/login', [ClientAuthController::class, 'login']);
    Route::get('/register', [ClientAuthController::class, 'showRegistrationForm'])->name('client.register');
    Route::post('/register', [ClientAuthController::class, 'register']);
    Route::post('/logout', [ClientAuthController::class, 'logout'])->name('client.logout');
    Route::get('/dashboard', function () {
        return "Client Dashboard";
    })->middleware('auth:client');
});

// Repeat this pattern for driver, employee, and admin routes
Route::prefix('driver')->group(function () {
    Route::get('/login', [DriverAuthController::class, 'showLoginForm'])->name('driver.login');
    Route::post('/login', [DriverAuthController::class, 'login']);
    Route::get('/register', [DriverAuthController::class, 'showRegistrationForm'])->name('driver.register');
    Route::post('/register', [DriverAuthController::class, 'register']);
    Route::post('/logout', [DriverAuthController::class, 'logout'])->name('driver.logout');
    Route::get('/dashboard', function () {
        return "driver Dashboard";
    })->middleware('auth:driver');
});

Route::prefix('employee')->group(function () {
    Route::get('/login', [EmployeeAuthController::class, 'showLoginForm'])->name('employee.login');
    Route::post('/login', [EmployeeAuthController::class, 'login']);
    Route::get('/register', [EmployeeAuthController::class, 'showRegistrationForm'])->name('employee.register');
    Route::post('/register', [EmployeeAuthController::class, 'register']);
    Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('employee.logout');
    Route::get('/dashboard', function () {
        return "employee Dashboard";
    })->middleware('auth:employee');
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::get('/register', [AdminAuthController::class, 'showRegistrationForm'])->name('admin.register');
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    Route::get('/dashboard', function () {
        return "admin Dashboard";
    })->middleware('auth:admin');
});
//-----------------------------------------------------------------------------------
//--------------------------------        END        --------------------------------
//-----------------------------------------------------------------------------------