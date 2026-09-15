<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Physical AUTOSECURE hardware (tracker or dashcam) bound to a vehicle.
 *
 * `provider` + `external_id` are the bridge to a third-party device platform.
 * Both are intentionally generic because no tracker/dashcam API has been
 * supplied yet — when it arrives only the provider driver changes, not this
 * schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('type', ['tracker', 'dashcam']);
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 64)->nullable();
            // Identifier used by the provider platform (IMEI, device id, ...).
            $table->string('external_id', 128)->nullable();
            $table->string('serial_number', 128)->unique();
            $table->string('imei', 32)->nullable()->unique();
            $table->string('sim_number', 32)->nullable();
            $table->string('phone_number', 32)->nullable();
            $table->string('brand', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('firmware_version', 64)->nullable();
            $table->enum('status', ['pending', 'active', 'offline', 'suspended', 'faulty', 'unbound'])
                ->default('pending');
            $table->boolean('is_online')->default(false);
            $table->timestamp('bound_at')->nullable();
            $table->timestamp('unbound_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->decimal('last_known_latitude', 10, 7)->nullable();
            $table->decimal('last_known_longitude', 10, 7)->nullable();
            // Provider-reported capability flags once documentation is available.
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'type']);
            $table->index(['provider', 'external_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
