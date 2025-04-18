<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([ // Use an array if calling multiple seeders
            LocationSeeder::class,
            ProductSeeder::class,
            ProductServiceSeeder::class,
            UserSeeder::class,
            SubscriptionSeeder::class,
            OrderSeeder::class,
            // Add other seeders here
        ]);
    }
}
