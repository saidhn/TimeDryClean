<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductService;

class ProductServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'غسيل عادي + كوي - Normal Wash & Iron'],
            ['name' => 'غسيل مستعجل + كوي - Express Wash & Iron'],
            ['name' => 'كوي عادي - Normal Iron'],
            ['name' => 'كوي مستعجل - Express Iron'],
        ];

        foreach ($services as $service) {
            ProductService::firstOrCreate($service);
        }
    }
}
