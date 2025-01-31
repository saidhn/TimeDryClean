<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products Table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('The name of the product');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Product Services Table
        Schema::create('product_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('The name of the product service');
            $table->decimal('price', 10, 2)->comment('The price per unit');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Advertisers Table
        Schema::create('advertisers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('The name of the advertiser');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Subscriptions Table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->boolean('paid')->default(false)->comment('Whether the subscription is paid');
            $table->text('benefit')->nullable()->comment('The benefits of the subscription');
            $table->date('start_date')->comment('The start date of the subscription');
            $table->date('end_date')->comment('The end date of the subscription');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Client Subscriptions Pivot Table
        Schema::create('client_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('The client associated with the subscription');
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade')->comment('The subscription associated with the client');
            $table->timestamps();
        });

        // Discounts Table
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['constant', 'percentage', 'number_of_free_products'])->comment('The type of discount: constant, percentage, number_of_free_products');
            $table->decimal('amount', 10, 2)->nullable()->comment('The amount of the discount');
            $table->foreignId('advertiser_id')->nullable()->constrained('advertisers')->onDelete('set null')->comment('The advertiser associated with the discount');
            $table->string('code')->unique()->comment('The unique discount code');
            $table->date('start_date')->comment('The start date of the discount');
            $table->date('end_date')->comment('The end date of the discount');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Discount Free Products Table
        Schema::create('discount_free_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->onDelete('cascade')->comment('The discount associated with the free products');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('The product associated with the discount');
            $table->integer('quantity')->default(1)->comment('The quantity of free products');
            $table->timestamps();
        });

        // Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('The client who placed the order');
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('set null')->comment('The discount applied to the order');
            $table->foreignId('client_subscription_id')->nullable()->constrained('client_subscriptions')->onDelete('set null')->comment('The subscription associated with the order');
            $table->decimal('sum_price', 10, 2)->comment('The total price of the order');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('The discount amount applied to the order');
            $table->enum('status', ['Pending', 'Processing', 'Completed'])->default('Pending')->comment('The status of the order');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Order Product Services Pivot Table
        Schema::create('order_product_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade')->comment('The order associated with the product service');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('The product associated with the order');
            $table->foreignId('product_service_id')->constrained('product_services')->onDelete('cascade')->comment('The product service associated with the order');
            $table->integer('quantity')->default(1)->comment('The quantity of the product service');
            $table->timestamps();
        });

        // Order Deliveries Table
        Schema::create('order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade')->comment('The order associated with the delivery');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('The driver assigned to the delivery');
            $table->enum('direction', ['orderToWork', 'workToOrder'])->comment('The direction of the delivery');
            $table->decimal('price', 10, 2)->comment('The price of the delivery');
            $table->enum('status', ['Assigned', 'En Route', 'Delivered'])->default('Assigned')->comment('The status of the delivery');
            $table->timestamp('delivery_date')->nullable()->comment('The date of delivery');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });
    }

    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraints issues
        Schema::dropIfExists('order_deliveries');
        Schema::dropIfExists('order_product_services');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('discount_free_products');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('client_subscriptions');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('advertisers');
        Schema::dropIfExists('product_services');
        Schema::dropIfExists('products');
    }
};
