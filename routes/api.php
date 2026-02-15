<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\Product\ProductController;

Route::middleware(['web', 'auth:admin,employee,driver,client'])->group(function () {
    Route::post('/orders/{order}/discount', [DiscountController::class, 'apply']);
    Route::delete('/orders/{order}/discount', [DiscountController::class, 'remove']);
    Route::post('/orders/{order}/discount/validate', [DiscountController::class, 'validate']);
    Route::get('/products/{product}/services', [ProductController::class, 'getServicePrices']);
});
