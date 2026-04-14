<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_services', function (Blueprint $table) {
            $table->unsignedInteger('points_at_order')->nullable()->after('price_at_order')
                ->comment('Points price per unit snapshot at time of order (for points-based orders)');
        });
    }

    public function down(): void
    {
        Schema::table('order_product_services', function (Blueprint $table) {
            $table->dropColumn('points_at_order');
        });
    }
};
