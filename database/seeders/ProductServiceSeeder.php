<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductService; // Import your ProductService model

class ProductServiceSeeder extends Seeder
{
    public function run()
    {
        $productServices = [
            ['name' => 'غسيل عادي - منتج خفيف - Normal Wash - Light Product', 'price' => 10],
            ['name' => 'غسيل عادي - منتج ثقيل - Normal Wash - Heavy Product', 'price' => 15],
            ['name' => 'غسيل مستعجل - منتج خفيف - Express Wash - Light Product', 'price' => 15],
            ['name' => 'غسيل مستعجل - منتج ثقيل - Express Wash - Heavy Product', 'price' => 20],
            ['name' => 'كوي عادي - منتج خفيف - Normal Ironing - Light Product', 'price' => 5],
            ['name' => 'كوي عادي - منتج ثقيل - Normal Ironing - Heavy Product', 'price' => 8],
            ['name' => 'كوي مستعجل - منتج خفيف - Express Ironing - Light Product', 'price' => 8],
            ['name' => 'كوي مستعجل - منتج ثقيل - Express Ironing - Heavy Product', 'price' => 12],
        ];

        ProductService::insert($productServices);
    }
}
