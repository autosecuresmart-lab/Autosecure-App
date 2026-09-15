<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable Coins ledger.
 *
 * Rows are append-only: a mistake is corrected with a compensating `reverse`
 * entry, never by editing history. `balance_after` makes the ledger auditable
 * and lets the admin portal detect drift.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('coin_wallet_id')->constrained('coin_wallets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['earn', 'pending', 'redeem', 'reverse', 'expire', 'adjustment']);
            // Signed: positive credits, negative debits.
            $table->bigInteger('coins');
            $table->unsignedBigInteger('balance_after');
            $table->string('description')->nullable();
            // What generated this entry (booking, payment, admin adjustment...).
            $table->nullableMorphs('source');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['coin_wallet_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
