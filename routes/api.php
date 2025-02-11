<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products/{product}/product-services', [ProductController::class, 'showProductServices'])->name('api.products.product-services');
