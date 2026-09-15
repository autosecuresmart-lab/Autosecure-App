<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nickname')->nullable();
            $table->string('plate_number', 32);
            $table->string('make', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('colour', 32)->nullable();
            $table->string('vin', 64)->nullable()->unique();
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric', 'cng', 'other'])->nullable();
            $table->enum('transmission', ['manual', 'automatic'])->nullable();
            $table->string('image_path')->nullable();
            // Odometer is authoritative when a tracker reports it, otherwise the
            // customer's manual entry is used. `odometer_source` records which.
            $table->unsignedBigInteger('odometer_km')->nullable();
            $table->enum('odometer_source', ['manual', 'tracker'])->default('manual');
            $table->timestamp('odometer_updated_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            // AutoDoc is a separate application; we only store the mapping.
            $table->string('autodoc_vehicle_ref')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
