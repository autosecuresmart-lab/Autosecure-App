<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * All money movements: subscription renewals and Finder bookings.
 *
 * `idempotency_key` is unique so a retried mobile request or a duplicated
 * gateway webhook can never create a second payment (idempotency requirement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // What the payment is for.
            $table->enum('purpose', ['subscription', 'booking', 'order', 'vendor_subscription', 'other'])
                ->default('subscription');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending', 'processing', 'successful', 'failed', 'reversed', 'refunded'])
                ->default('pending');
            $table->enum('channel', ['card', 'bank_transfer', 'ussd', 'wallet', 'coins', 'cash', 'other'])
                ->default('card');
            // Gateway driver name; actual provider is PENDING sign-off.
            $table->string('gateway', 64)->nullable();
            $table->string('gateway_reference', 191)->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('initiated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('gateway_reference');
        });

        // `subscriptions` is created earlier; link the most recent payment.
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreign('last_payment_id')->references('id')->on('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['last_payment_id']);
        });

        Schema::dropIfExists('payments');
    }
};
