<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPaymentFulfillmentDecoupledTest extends TestCase
{
    use RefreshDatabase;

    public function test_paying_for_an_order_does_not_mark_it_delivered(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000090', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000091', 'balance' => 100]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $response = $this->actingAs($admin, 'admin')->post(route('orders.store'), [
            'user_id' => $client->id,
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'money',
        ]);

        $order = Order::latest('id')->first();

        $this->assertTrue($order->is_paid);
        $this->assertSame(OrderStatus::PLACED, $order->status);
        $this->assertNotEquals(OrderStatus::DELIVERED, $order->status);
    }
}
