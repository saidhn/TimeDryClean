<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\OrderProductService; // Import the pivot model
use App\Enums\OrderStatus;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $clients = User::where('user_type', 'client')->get();
        $products = Product::all();
        $productServices = ProductService::all();

        $this->command->info('Products count: ' . $products->count());
        $this->command->info('Product Services count: ' . $productServices->count());

        if ($products->isEmpty()) {
            $this->command->error('Error: Products table is empty.');
            return;
        }

        if ($productServices->isEmpty()) {
            $this->command->error('Error: Product Services table is empty.');
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
                'discount_id' => null, // Discount is always null
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
            $randomProducts = [];

            if ($products->isNotEmpty()) {
                $randomProducts = $products->random(rand(1, min(5, $products->count())));
            }

            foreach ($randomProducts as $product) {
                if ($productServices->isNotEmpty()) {
                    $quantity = rand(1, 3);
                    $service = $productServices->random();

                    // Create OrderProductService record directly
                    OrderProductService::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_service_id' => $service->id,
                        'quantity' => $quantity,
                    ]);

                    $totalPrice += $service->price * $quantity;
                }
            }

            $order->sum_price = $totalPrice;
            $order->save();
        }

        $this->command->info('300 orders created successfully.');
    }
}