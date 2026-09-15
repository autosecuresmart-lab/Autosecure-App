<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor verification pipeline (proposal section 08).
 *
 * One row per stage per review round, so a vendor's full verification history
 * is auditable: application -> identity -> business -> location -> payout ->
 * approval -> monitoring.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->enum('stage', ['application', 'identity', 'business', 'location', 'payout', 'approval', 'monitoring']);
            $table->enum('status', ['pending', 'passed', 'needs_update', 'rejected'])->default('pending');
            $table->foreignId('reviewer_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('documents')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'stage']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_verifications');
    }
};
