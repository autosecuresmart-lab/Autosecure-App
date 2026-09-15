<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Location history. Powers live location, trip playback, "find my car" and the
 * last-known position shown during a theft event.
 *
 * This is the highest-volume table in the system, so it is written in narrow
 * rows and indexed by (device_id, recorded_at) for playback range scans.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_positions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_kph', 6, 2)->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->decimal('altitude_m', 8, 2)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->boolean('ignition')->nullable();
            $table->boolean('moving')->nullable();
            // Which positioning source produced the fix. Mirrors the GPRS/GPS
            // and LBS/WiFi fallbacks described in the device protocol docs.
            $table->enum('source', ['gps', 'lbs', 'wifi', 'gps_lbs', 'manual', 'unknown'])->default('gps');
            $table->timestamp('recorded_at');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'recorded_at']);
            $table->index(['vehicle_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_positions');
    }
};
