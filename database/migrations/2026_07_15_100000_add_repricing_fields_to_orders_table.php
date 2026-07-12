<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('repriced_amount', 10, 2)->nullable()->after('sum_price');
            $table->timestamp('repriced_at')->nullable()->after('repriced_amount');
            $table->foreignId('repriced_by')->nullable()->after('repriced_at')
                ->constrained('users')->onDelete('set null');
            $table->boolean('requires_additional_payment')->default(false)->after('repriced_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['repriced_by']);
            $table->dropColumn(['repriced_amount', 'repriced_at', 'repriced_by', 'requires_additional_payment']);
        });
    }
};
