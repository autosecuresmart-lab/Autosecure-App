<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Car Theft Trigger workflow (proposal section 04).
 *
 * Step 5 of the workflow requires the trigger time, user, vehicle, commands,
 * responses and status changes to be recorded. This table is that record, and
 * `device_commands.theft_event_id` links every command issued under it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theft_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->enum('status', ['open', 'acknowledged', 'resolved', 'false_alarm'])
                ->default('open');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('high');
            // Step 2: PIN or biometric confirmation is mandatory.
            $table->boolean('pin_verified')->default(false);
            $table->boolean('biometric_verified')->default(false);
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('resolution_note')->nullable();
            // Snapshot of the vehicle position at trigger time.
            $table->decimal('last_known_latitude', 10, 7)->nullable();
            $table->decimal('last_known_longitude', 10, 7)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'status']);
            $table->index('triggered_at');
        });

        // `device_commands` is created earlier, so the link is added here.
        Schema::table('device_commands', function (Blueprint $table) {
            $table->foreign('theft_event_id')->references('id')->on('theft_events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('device_commands', function (Blueprint $table) {
            $table->dropForeign(['theft_event_id']);
        });

        Schema::dropIfExists('theft_events');
    }
};
