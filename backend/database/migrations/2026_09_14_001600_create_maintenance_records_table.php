<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vehicle Care Memory (proposal section 03).
 *
 * One row per care event. Reminders are derived from `next_due_at` /
 * `next_due_odometer_km` so that both time-based and mileage-based rules work.
 * `reminder_status` is stored (rather than computed on read) because the app
 * must show Due Soon / Due Now / Overdue consistently across screens.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('category', [
                'oil_change',
                'brake_service',
                'tyre_replacement',
                'battery',
                'general_service',
                'repair',
                'inspection',
                'other',
            ]);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('performed_at');
            $table->unsignedBigInteger('odometer_km')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            // Free-text workshop name for records entered manually.
            $table->string('workshop_name')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->json('details')->nullable();
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            // Reminder targets (either or both may be set).
            $table->date('next_due_at')->nullable();
            $table->unsignedBigInteger('next_due_odometer_km')->nullable();
            $table->enum('reminder_status', ['upcoming', 'due_soon', 'due', 'overdue', 'completed'])
                ->default('upcoming');
            $table->timestamp('reminder_notified_at')->nullable();
            // Manual vs tracker-sourced mileage, and who entered it.
            $table->enum('odometer_source', ['manual', 'tracker'])->default('manual');
            $table->foreignId('booking_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'category']);
            $table->index(['vehicle_id', 'reminder_status']);
            $table->index(['vehicle_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
