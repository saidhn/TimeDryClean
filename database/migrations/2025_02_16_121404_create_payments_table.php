<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // User making the payment
            $table->decimal('amount', 10, 2); // Amount of the payment
            $table->string('payment_method')->nullable(); // E.g., credit card, PayPal, etc.
            $table->string('transaction_id')->nullable(); // ID from the payment gateway
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending'); // Payment status
            $table->timestamp('payment_date')->nullable(); // Date and time of payment
            $table->text('details')->nullable(); // Additional details about the payment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
