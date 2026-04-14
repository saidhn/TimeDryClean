<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['money', 'points'])->default('money')->after('status')
                ->comment('Whether the order is paid with money (KWD balance) or points');
            $table->unsignedBigInteger('points_used')->default(0)->after('payment_method')
                ->comment('Total points charged for this order (snapshot at time of order)');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'points_used']);
        });
    }
};
