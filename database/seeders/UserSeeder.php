<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all addresses to associate users with
        $addresses = Address::all();

        // Define user data
        $users = [
            [
                'name' => 'خالد احمد',
                'mobile' => '1234567890',
                'email' => 'client1@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'client',
            ],
            [
                'name' => 'حسام علي',
                'mobile' => '0987654321',
                'email' => 'driver1@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'driver',
            ],
            [
                'name' => 'موسى محمود',
                'mobile' => '1122334455',
                'email' => 'employee1@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'employee',
            ],
            [
                'name' => 'سعيد',
                'mobile' => '5566778899',
                'email' => 'admin1@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
            ],
        ];

        // Create users and associate them with random addresses
        foreach ($users as $userData) {
            $user = User::create($userData);

            // Assign a random address to the user
            if ($addresses->isNotEmpty()) {
                $randomAddress = $addresses->random();
                $user->address_id = $randomAddress->id;
                $user->save();
            }
        }
    }
}
