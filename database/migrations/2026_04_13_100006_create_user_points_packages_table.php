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
            $table->foreignId('points_package_id')->constrained('points_packages')->onDelete('cascade');
            $table->unsignedInteger('points_awarded');
            $table->decimal('price_paid_kwd', 10, 3)->default(0);
            $table->enum('payment_method', ['knet', 'manual'])->default('manual')
                ->comment('knet = bought by client online; manual = added by employee/admin');
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Employee/admin who manually added this');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_points_packages');
    }
};
