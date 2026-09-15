<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scheduled care reminders.
 *
 * Kept separate from `maintenance_records` so that a reminder can exist before
 * any work is done, can be rescheduled/dismissed, and can be delivered through
 * more than one channel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('maintenance_record_id')->nullable()->constrained('maintenance_records')->nullOnDelete();
            $table->string('category', 64);
            $table->string('title');
            $table->date('due_at')->nullable();
            $table->unsignedBigInteger('due_odometer_km')->nullable();
            $table->enum('status', ['pending', 'due_soon', 'due', 'overdue', 'dismissed', 'completed'])
                ->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'status']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_reminders');
    }
};
