<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_kwd', 10, 3)->comment('How much the client pays in KWD');
            $table->decimal('points', 12, 2)->comment('Points awarded upon purchase');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_packages');
    }
};
