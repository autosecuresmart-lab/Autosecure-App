<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer's subscription lifecycle.
 *
 * Only one row per customer should be `active` / `grace` at a time (enforced in
 * the service layer, since MySQL cannot express this partial constraint
 * portably). A lapsed subscription downgrades entitlements but never removes
 * the core tracker/dashcam security functions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans');
            $table->enum('status', ['pending', 'active', 'grace', 'expired', 'cancelled', 'failed'])
                ->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // Access continues until here after `ends_at` while payment is chased.
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('renewed_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->unsignedTinyInteger('failed_payment_attempts')->default(0);
            $table->foreignId('last_payment_id')->nullable();
            $table->enum('source', ['purchase', 'admin', 'coin_redemption', 'trial', 'promo'])->default('purchase');
            $table->string('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
