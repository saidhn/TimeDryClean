<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create provinces table
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('The name of the province');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Create cities table
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('The name of the city');
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Create addresses table
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('cascade');
            $table->string('street')->nullable()->comment('The street name');
            $table->string('building')->nullable()->comment('The building number or name');
            $table->integer('floor')->nullable()->comment('The floor number');
            $table->string('apartment_number')->nullable()->comment('The apartment number');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes
        });

        // Modify the users table (or create it if it doesn't exist)
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->enum('user_type', ['client', 'driver', 'employee', 'admin'])->default('client')->comment('The type of user');
            $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('set null')->comment('The address of the user');
        });

        // Create separate password resets tables to keep them separate
        Schema::create('client_password_resets', function (Blueprint $table) {
            $table->string('mobile')->index()->comment('The mobile number for password reset');
            $table->string('token')->comment('The password reset token');
            $table->timestamp('created_at')->nullable()->comment('The timestamp when the token was created');
        });

        Schema::create('driver_password_resets', function (Blueprint $table) {
            $table->string('mobile')->index()->comment('The mobile number for password reset');
            $table->string('token')->comment('The password reset token');
            $table->timestamp('created_at')->nullable()->comment('The timestamp when the token was created');
        });

        Schema::create('employee_password_resets', function (Blueprint $table) {
            $table->string('mobile')->index()->comment('The mobile number for password reset');
            $table->string('token')->comment('The password reset token');
            $table->timestamp('created_at')->nullable()->comment('The timestamp when the token was created');
        });

        Schema::create('admin_password_resets', function (Blueprint $table) {
            $table->string('mobile')->index()->comment('The mobile number for password reset');
            $table->string('token')->comment('The password reset token');
            $table->timestamp('created_at')->nullable()->comment('The timestamp when the token was created');
        });
    }

    public function down(): void
    {
        // Drop foreign key constraints and columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
            $table->dropForeign(['address_id']); // Drop the foreign key constraint first
            $table->dropColumn('address_id');
            $table->dropColumn('user_type');
        });

        // Drop password reset tables
        Schema::dropIfExists('client_password_resets');
        Schema::dropIfExists('driver_password_resets');
        Schema::dropIfExists('employee_password_resets');
        Schema::dropIfExists('admin_password_resets');

        // Drop addresses, cities, and provinces tables
        Schema::dropIfExists('addresses'); // Then drop addresses
        Schema::dropIfExists('cities');    // Then drop cities
        Schema::dropIfExists('provinces'); // Finally drop provinces
    }
};
