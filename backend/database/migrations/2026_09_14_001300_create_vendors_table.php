<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finder marketplace vendors.
 *
 * `status` is the single source of trust: only `verified` + active
 * subscription vendors are publicly searchable and able to take bookings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_category_id')->nullable()->constrained('vendor_categories')->nullOnDelete();
            $table->string('business_name');
            $table->string('trading_name')->nullable();
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('address_line')->nullable();
            $table->string('city', 64)->nullable();
            $table->string('state', 64)->nullable();
            $table->string('country', 64)->default('Nigeria');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('service_radius_km')->nullable();
            $table->json('opening_hours')->nullable();
            $table->string('cancellation_policy')->nullable();
            $table->enum('status', ['pending', 'under_review', 'verified', 'rejected', 'suspended', 'expired'])
                ->default('pending');
            $table->boolean('is_publicly_visible')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->decimal('commission_percent', 5, 2)->nullable();
            // Vendor subscription (separate revenue line from customer premium).
            $table->decimal('subscription_fee', 12, 2)->nullable();
            $table->timestamp('subscription_starts_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->json('settlement_details')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_publicly_visible']);
            $table->index(['vendor_category_id', 'status']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
