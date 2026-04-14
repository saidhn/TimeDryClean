<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_points_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('points_package_id')->constrained('points_packages')->onDelete('restrict');
            $table->decimal('points_awarded', 12, 2)->comment('Points actually awarded (snapshot)');
            $table->decimal('price_paid_kwd', 10, 3)->nullable()->comment('KWD paid (null = manually added by employee)');
            $table->string('payment_method')->nullable()->comment('knet | manual');
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null')->comment('Employee/admin who manually added');
            $table->string('transaction_id')->nullable()->comment('KNET transaction if purchased');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_points_packages');
    }
};
