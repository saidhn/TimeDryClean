<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['money', 'points'])->default('money')->after('notes')->comment('Whether order is paid with KWD balance or points');
            $table->decimal('points_used', 12, 2)->default(0)->after('payment_method')->comment('Total points charged for points orders');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'points_used']);
        });
    }
};
