<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer subscription plans.
 *
 * Prices here are seeded defaults from the proposal (Monthly ₦4,200,
 * Half-Year ₦23,940, Yearly ₦45,360) and are pending final management
 * sign-off. The admin portal owns them afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('base_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->enum('interval', ['monthly', 'half_yearly', 'yearly', 'custom'])->default('monthly');
            $table->unsignedSmallInteger('duration_days');
            $table->decimal('discount_percent', 5, 2)->default(0);
            // The free tier is a plan row so entitlement checks have one code path.
            $table->boolean('is_free')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
