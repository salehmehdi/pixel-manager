<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pixel Manager SQL tables migration.
 *
 * Run with: php artisan migrate
 *
 * This creates the necessary tables for SQL-based storage.
 * Use this if you prefer MySQL/PostgreSQL/SQLite over MongoDB.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Credentials table
        Schema::create('pixel_manager_credentials', function (Blueprint $table) {
            $table->id();
            $table->integer('app_id')->unique();
            $table->string('category', 50)->default('customer_event');
            $table->json('data'); // Stores all platform credentials as JSON
            $table->timestamps();

            $table->index(['app_id', 'category']);
        });

        // Events log table
        Schema::create('pixel_manager_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 100)->unique();
            $table->string('event_type', 50);
            $table->string('event_name', 100);
            $table->decimal('value', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();

            // Customer information
            $table->string('customer_email', 255)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_first_name', 100)->nullable();
            $table->string('customer_last_name', 100)->nullable();
            $table->string('customer_city', 100)->nullable();
            $table->string('customer_country', 2)->nullable();

            // Technical information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Platform destinations
            $table->json('destinations'); // Array of platform names

            // Full event data for reference
            $table->json('event_data');

            $table->timestamp('created_at')->useCurrent();

            // Indexes for common queries
            $table->index('event_type');
            $table->index('event_name');
            $table->index('created_at');
            $table->index(['event_type', 'created_at']);
            $table->index('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pixel_manager_events');
        Schema::dropIfExists('pixel_manager_credentials');
    }
};
