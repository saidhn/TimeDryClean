<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'discount_type')) {
                $table->enum('discount_type', ['fixed', 'percentage'])->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_value');
            }
            if (!Schema::hasColumn('orders', 'discount_applied_by')) {
                $table->unsignedBigInteger('discount_applied_by')->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'discount_applied_at')) {
                $table->timestamp('discount_applied_at')->nullable()->after('discount_applied_by');
            }
        });
        
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'idx_discount_applied_by')) {
                $table->index('discount_applied_by', 'idx_discount_applied_by');
            }
            if (!$this->indexExists('orders', 'idx_discount_type')) {
                $table->index('discount_type', 'idx_discount_type');
            }
            
            if (!$this->foreignKeyExists('orders', 'orders_discount_applied_by_foreign')) {
                $table->foreign('discount_applied_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            }
        });
        
        try {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT chk_discount_value_positive CHECK (discount_value IS NULL OR discount_value > 0)');
        } catch (\Exception $e) {
        }
        
        try {
            DB::statement('ALTER TABLE orders ADD CONSTRAINT chk_discount_amount_positive CHECK (discount_amount IS NULL OR discount_amount >= 0)');
        } catch (\Exception $e) {
        }
    }
    
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return count($indexes) > 0;
    }
    
    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$table, $foreignKey]);
        return count($constraints) > 0;
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS chk_discount_value_positive');
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS chk_discount_amount_positive');
            
            $table->dropForeign(['discount_applied_by']);
            $table->dropIndex('idx_discount_applied_by');
            $table->dropIndex('idx_discount_type');
            
            $table->dropColumn([
                'discount_type',
                'discount_value',
                'discount_amount',
                'discount_applied_by',
                'discount_applied_at'
            ]);
        });
    }
};
