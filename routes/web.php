<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\DriverAuthController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientOrderController;
use App\Http\Controllers\Client\ClientProfileController;
use App\Http\Controllers\Client\ClientSettingsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminManageOrdersController;
use App\Http\Controllers\Admin\AdminManageUsersController;
use App\Http\Controllers\product_services\ProductServiceController;
use App\Http\Controllers\products\ProductController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/language/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ar'])) { // Validate locale
        Session::put('locale', $locale);
    }
    return redirect()->back(); // Redirect back to the previous page
})->name('set_language');

Route::middleware(['set_locale'])->group(function () {
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
    });

    Route::prefix('employee')->group(function () {
        Route::get('/login', [EmployeeAuthController::class, 'showLoginForm'])->name('employee.login');
        Route::post('/login', [EmployeeAuthController::class, 'login'])->name('employee.login.post');
        Route::get('/register', [EmployeeAuthController::class, 'showRegistrationForm'])->name('employee.register');
        Route::post('/register', [EmployeeAuthController::class, 'register'])->name('employee.register.post');
        Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('employee.logout');
    });

    Route::prefix('admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
        Route::get('/register', [AdminAuthController::class, 'showRegistrationForm'])->name('admin.register');
        Route::post('/register', [AdminAuthController::class, 'register'])->name('admin.register.post');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    });

    //-----------------------------------------------------------------------------------
    //--------------------------------    Client Routes    ------------------------------
    //-----------------------------------------------------------------------------------
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
    //--------------------------------    Admin Routes    ------------------------------
    //-----------------------------------------------------------------------------------
    Route::prefix('admin')->middleware('auth:admin')->group(function () {
        //dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        //users
        Route::get('/users', [AdminManageUsersController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [AdminManageUsersController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [AdminManageUsersController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{user}', [AdminManageUsersController::class, 'show'])->name('admin.users.show');
        Route::get('/users/{user}/edit', [AdminManageUsersController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{user}', [AdminManageUsersController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [AdminManageUsersController::class, 'destroy'])->name('admin.users.destroy');
        //orders
        Route::get('/orders', [AdminManageOrdersController::class, 'index'])->name('admin.orders.index');
        Route::get('/orders/create', [AdminManageOrdersController::class, 'create'])->name('admin.orders.create');
        Route::post('/orders', [AdminManageOrdersController::class, 'store'])->name('admin.orders.store');

        // Route::get('/orders/{order}', [AdminManageOrdersController::class, 'orderShow'])->name('admin.orders.show');
        // Route::get('/orders/{order}/edit', [AdminManageOrdersController::class, 'orderEdit'])->name('admin.orders.edit');
        // Route::put('/orders/{order}', [AdminManageOrdersController::class, 'orderUpdate'])->name('admin.orders.update');
        // Route::delete('/orders/{order}', [AdminManageOrdersController::class, 'orderDestroy'])->name('admin.orders.destroy');
    });

    //-----------------------------------------------------------------------------------
    //--------------------------------      products        --------------------------------
    //-----------------------------------------------------------------------------------
    //product resource route
    Route::resource('products', ProductController::class)->middleware('auth:admin,employee,driver');
    //product services resource route
    Route::resource('product_services', ProductServiceController::class)->middleware('auth:admin,employee');
});//end set locale
//-----------------------------------------------------------------------------------
//--------------------------------        END        --------------------------------
//-----------------------------------------------------------------------------------