<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductServicePrice;
use App\Models\OrderProductService;
use App\Enums\OrderStatus;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $clients = User::where('user_type', 'client')->get();
        $productServicePrices = ProductServicePrice::with('product', 'productService')->get();

        $this->command->info('ProductServicePrices count: ' . $productServicePrices->count());

        if ($productServicePrices->isEmpty()) {
            $this->command->error('Error: ProductServicePrices table is empty. Run ProductAndPriceSeeder first.');
            return;
        }

        if ($clients->isEmpty()) {
            $this->command->info('Warning: No clients found. Orders not created.');
            return;
        }

        for ($i = 0; $i < 300; $i++) {
            $client = $clients->random();
            $order = Order::create([
                'user_id' => $client->id,
                'discount_id' => null,
                'sum_price' => 0,
                'discount_amount' => 0,
                'status' => $faker->randomElement([
                    OrderStatus::PENDING,
                    OrderStatus::PROCESSING,
                    OrderStatus::SHIPPED,
                    OrderStatus::COMPLETED,
                    OrderStatus::CANCELLED,
                ]),
            ]);

            $totalPrice = 0;
            $count = rand(1, min(5, $productServicePrices->count()));
            $selectedPrices = $productServicePrices->random($count);

            foreach ($selectedPrices as $priceRow) {
                $quantity = rand(1, 3);
                $lineTotal = $priceRow->price * $quantity;
                $totalPrice += $lineTotal;

                OrderProductService::create([
                    'order_id' => $order->id,
                    'product_id' => $priceRow->product_id,
                    'product_service_id' => $priceRow->product_service_id,
                    'quantity' => $quantity,
                    'price_at_order' => $priceRow->price,
                ]);
            }

            $order->sum_price = $totalPrice;
            $order->save();
        }

        $this->command->info('300 orders created successfully.');
    }
}