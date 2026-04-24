<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the payment_method enum to include 'knet'
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('money', 'points', 'knet') NOT NULL DEFAULT 'money'");

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable()->after('payment_method')
                ->comment('FK to payments table for KNET orders');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('money', 'points') NOT NULL DEFAULT 'money'");
    }
};
