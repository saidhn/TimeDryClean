<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Period defines how long the user can benefit from the subscription (e.g., 1 month, 2 months).
     */
    public function run()
    {
        $subscriptions = [
            ['paid' => 20, 'benefit' => 30, 'period_duration' => 1, 'period_unit' => 'month'],
            ['paid' => 30, 'benefit' => 45, 'period_duration' => 1, 'period_unit' => 'month'],
            ['paid' => 40, 'benefit' => 60, 'period_duration' => 2, 'period_unit' => 'month'],
        ];

        foreach ($subscriptions as $subscription) {
            DB::table('subscriptions')->insert([
                'paid' => $subscription['paid'],
                'benefit' => $subscription['benefit'],
                'period_duration' => $subscription['period_duration'],
                'period_unit' => $subscription['period_unit'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
