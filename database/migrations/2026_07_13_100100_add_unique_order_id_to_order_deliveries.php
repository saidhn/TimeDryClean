<?php
/**
 * Before deploying to any environment with existing data, run:
 *   SELECT order_id, COUNT(*) c FROM order_deliveries GROUP BY order_id HAVING c > 1;
 * and resolve any duplicates manually — this migration will fail to apply otherwise.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_deliveries', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });
    }
};
