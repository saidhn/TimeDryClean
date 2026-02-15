<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductService; // Import your ProductService model

class ProductServiceSeeder extends Seeder
{
    public function run()
    {
        $productServices = [
            ['name' => 'غسيل عادي - Normal Wash'],
            ['name' => 'غسيل مستعجل - Express Wash'],
            ['name' => 'كوي عادي - Normal Ironing'],
            ['name' => 'كوي مستعجل - Express Ironing'],
        ];

        ProductService::insert($productServices);
    }
}
