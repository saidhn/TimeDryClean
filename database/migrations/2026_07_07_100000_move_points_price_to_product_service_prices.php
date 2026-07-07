<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_service_prices', function (Blueprint $table) {
            $table->decimal('points_price', 12, 2)->nullable()->after('price')
                ->comment('Price in points per unit for this product-service combination; null means not payable with points');
        });

        // Preserve existing data: copy each product's points price to all of its configured services
        $products = DB::table('products')->whereNotNull('points_price')->get(['id', 'points_price']);
        foreach ($products as $product) {
            DB::table('product_service_prices')
                ->where('product_id', $product->id)
                ->update(['points_price' => $product->points_price]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('points_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('points_price', 12, 2)->nullable()->after('image_path')
                ->comment('Price in points per unit for this product (applies to any service)');
        });

        // Restore a product-level points price from its service rows (best effort)
        $prices = DB::table('product_service_prices')
            ->whereNotNull('points_price')
            ->orderBy('id')
            ->get(['product_id', 'points_price']);
        foreach ($prices->groupBy('product_id') as $productId => $rows) {
            DB::table('products')
                ->where('id', $productId)
                ->update(['points_price' => $rows->first()->points_price]);
        }

        Schema::table('product_service_prices', function (Blueprint $table) {
            $table->dropColumn('points_price');
        });
    }
};
