<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('ar_SA'); // Use Arabic Faker
        $addresses = Address::all();

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

        foreach ($users as $userData) {
            $user = User::create($userData);
            if ($addresses->isNotEmpty()) {
                $randomAddress = $addresses->random();
                $user->address_id = $randomAddress->id;
                $user->save();
            }
        }

        // Generate 196 more users using Faker
        for ($i = 0; $i < 196; $i++) {
            $userType = $faker->randomElement(['client', 'driver', 'employee']);
            $name = $faker->name;
            $mobile = $faker->numerify('05########'); // Generates 05 followed by 8 random digits
            $email = $faker->unique()->safeEmail;
            $password = Hash::make('password123');

            $user = User::create([
                'name' => $name,
                'mobile' => $mobile,
                'email' => $email,
                'password' => $password,
                'user_type' => $userType,
            ]);

            if ($addresses->isNotEmpty()) {
                $randomAddress = $addresses->random();
                $user->address_id = $randomAddress->id;
                $user->save();
            }
        }
    }
}