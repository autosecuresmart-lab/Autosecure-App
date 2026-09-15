<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finder bookings (services) and orders (parts).
 *
 * Both share this table because they share a lifecycle, a payment and a
 * commission calculation. `type` distinguishes the fulfilment model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Human-readable reference shown in receipts and admin reports.
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->enum('type', ['booking', 'order'])->default('booking');
            $table->enum('status', [
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'cancelled',
                'disputed',
                'refunded',
            ])->default('pending');
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'partially_refunded', 'refunded'])
                ->default('unpaid');
            $table->enum('fulfilment', ['in_store', 'mobile', 'delivery', 'pickup'])->default('in_store');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('coins_redeemed', 12, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->text('customer_note')->nullable();
            $table->text('vendor_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'scheduled_at']);
            $table->index('payment_status');
        });

        // `maintenance_records` is created earlier; link it to the booking that
        // produced the work so a completed booking can auto-create a care record.
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });

        Schema::dropIfExists('bookings');
    }
};
