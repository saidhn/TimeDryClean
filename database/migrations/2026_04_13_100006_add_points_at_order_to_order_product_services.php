<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_product_services', function (Blueprint $table) {
            $table->decimal('points_at_order', 12, 2)->nullable()->after('price_at_order')->comment('Points price per unit at the time of order (for points orders)');
        });
    }

    public function down(): void
    {
        Schema::table('order_product_services', function (Blueprint $table) {
            $table->dropColumn('points_at_order');
        });
    }
};
