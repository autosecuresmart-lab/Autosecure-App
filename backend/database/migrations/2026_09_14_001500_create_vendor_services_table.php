<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services and products offered by a vendor. A single table covers both
 * because bookings and orders share the same line-item shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('vendor_category_id')->nullable()->constrained('vendor_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['service', 'product'])->default('service');
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_bookable')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'is_active']);
            $table->index(['vendor_category_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_services');
    }
};
