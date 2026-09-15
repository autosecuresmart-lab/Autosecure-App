<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every command sent to a device, with its full lifecycle.
 *
 * Critical rule (product requirement): a command is only ever reported as
 * successful when the device has acknowledged it. `acknowledged_at` is set from
 * the device response, never optimistically. `idempotency_key` prevents a
 * retried mobile request from immobilising a vehicle twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('theft_event_id')->nullable();
            $table->string('type', 64);
            $table->enum('status', ['pending', 'sent', 'acknowledged', 'failed', 'timeout', 'cancelled'])
                ->default('pending');
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('provider_reference', 128)->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->unsignedTinyInteger('attempts')->default(0);
            // Set when the customer confirmed with PIN/biometric. Required for
            // destructive commands such as remote shutdown.
            $table->boolean('confirmed_by_user')->default(false);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['vehicle_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
