<?php

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiscountController;

Route::middleware('auth')->group(function () {
    Route::post('/orders/{order}/discount', [DiscountController::class, 'apply']);
    Route::delete('/orders/{order}/discount', [DiscountController::class, 'remove']);
    Route::post('/orders/{order}/discount/validate', [DiscountController::class, 'validate']);
});
