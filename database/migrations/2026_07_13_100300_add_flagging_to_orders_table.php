<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('status');
            $table->string('flag_reason', 255)->nullable()->after('is_flagged');
            $table->timestamp('flagged_at')->nullable()->after('flag_reason');
            $table->foreignId('flagged_by')->nullable()->after('flagged_at')
                ->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['flagged_by']);
            $table->dropColumn(['is_flagged', 'flag_reason', 'flagged_at', 'flagged_by']);
        });
    }
};
