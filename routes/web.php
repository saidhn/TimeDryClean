<?php

use App\Enums\UserType;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\DriverAuthController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientProfileController;
use App\Http\Controllers\Client\ClientSettingsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminManageUsersController;
use App\Http\Controllers\Admin\client\AdminManageClientSubscriptionsController;
use App\Http\Controllers\orders\OrderAssignmentController;
use App\Http\Controllers\orders\OrdersController;
use App\Http\Controllers\product_services\ProductServiceController;
use App\Http\Controllers\products\ProductController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Models\User;

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
        Route::resource('/client_subscriptions', AdminManageClientSubscriptionsController::class); // Nested route
    });

    //-----------------------------------------------------------------------------------
    //--------------------------------      products        -----------------------------
    //-----------------------------------------------------------------------------------
    //product resource route
    Route::resource('products', ProductController::class)->middleware('auth:admin,employee,driver');
    //product services resource route
    Route::resource('product_services', ProductServiceController::class)->middleware('auth:admin,employee');


    //-----------------------------------------------------------------------------------
    //--------------------------------       orders       -------------------------------
    //-----------------------------------------------------------------------------------
    Route::resource('orders', OrdersController::class)->middleware('auth:admin,employee,driver,client'); // needs modification to prevent clients from editing orders

    //search clients inside orders
    Route::get('/users/search', function (Request $request) {
        $search = $request->input('q');
        $userType = $request->input('user_type');
        $page = $request->input('page', 1);
        $perPage = 10;

        $usersQuery = User::query();

        if ($userType) {
            $validUserTypes = [
                UserType::ADMIN,
                UserType::CLIENT,
                UserType::DRIVER,
                UserType::EMPLOYEE,
            ];

            if (in_array($userType, $validUserTypes)) {
                $usersQuery->where('user_type', $userType);
            } else {
                return response()->json(['error' => 'Invalid user type'], 400);
            }
        }

        if ($search) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");

                $query->orWhere(function ($query) use ($search) {
                    $query->whereRaw("REPLACE(REPLACE(REPLACE(mobile, ' ', ''), '-', ''), '+', '') LIKE ?", ["%$search%"]);
                });

                if (is_numeric($search)) {
                    $query->orWhere('id', $search);
                }
            });
        }

        $users = $usersQuery->paginate($perPage, ['*'], 'page', $page);

        return response()->json($users);
    });

    Route::middleware(['auth:admin,employee'])->group(function () {
        Route::get('/orders_search', [OrderAssignmentController::class, 'searchOrders']);
        Route::get('/orders_assign', [OrderAssignmentController::class, 'showAssignmentForm'])->name('orders.assign.form');
        Route::post('/orders_assign', [OrderAssignmentController::class, 'assignOrder'])->name('orders.assign');
        Route::get('/orders_recommend/{order}/recommend-driver', [OrderAssignmentController::class, 'recommendDriver'])->name('orders.recommend.driver');
    });
    
    //-----------------------------------------------------------------------------------
    //--------------------------------   subscriptions   --------------------------------
    //-----------------------------------------------------------------------------------
    Route::resource('subscriptions', SubscriptionController::class)->middleware('auth:admin,employee,driver,client');
}); //end set locale
//-----------------------------------------------------------------------------------
//--------------------------------        END        --------------------------------
//-----------------------------------------------------------------------------------