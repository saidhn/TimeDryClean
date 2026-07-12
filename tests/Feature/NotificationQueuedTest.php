<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionNotificationJob;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationQueuedTest extends TestCase
{
    use RefreshDatabase;

    public function test_placing_an_order_queues_a_notification_job_instead_of_sending_inline(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['user_type' => 'admin', 'mobile' => '50000120', 'balance' => 0]);
        $client = User::factory()->create(['user_type' => 'client', 'mobile' => '50000121', 'balance' => 100]);
        $product = Product::create(['name' => 'Shirt']);
        $service = ProductService::create(['name' => 'Wash']);
        ProductServicePrice::create(['product_id' => $product->id, 'product_service_id' => $service->id, 'price' => 5]);

        $this->actingAs($admin, 'admin')->post(route('orders.store'), [
            'user_id' => $client->id,
            'order_product_services' => [
                ['product_id' => $product->id, 'product_service_id' => $service->id, 'quantity' => 1],
            ],
            'payment_method' => 'money',
        ]);

        Queue::assertPushed(SendTransactionNotificationJob::class, function ($job) use ($client) {
            return $job->userId === $client->id && $job->messageKey === 'order_placed_balance';
        });
    }
}
