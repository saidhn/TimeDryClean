<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currentDate = Carbon::now();
        $endDate = $currentDate->copy()->addYear();

        $subscriptions = [
            ['paid' => 20, 'benefit' => 30],
            ['paid' => 30, 'benefit' => 45],
            ['paid' => 40, 'benefit' => 60],
        ];

        foreach ($subscriptions as $subscription) {
            DB::table('subscriptions')->insert([
                'paid' => $subscription['paid'],
                'benefit' => $subscription['benefit'],
                'start_date' => $currentDate,
                'end_date' => $endDate,
                'created_at' => now(), // Important: Add timestamps
                'updated_at' => now(), // Important: Add timestamps
            ]);
        }
    }
}
