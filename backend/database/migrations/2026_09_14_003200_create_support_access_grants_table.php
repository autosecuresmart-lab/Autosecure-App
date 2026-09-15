<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Controlled support access to sensitive customer data.
 *
 * A support agent cannot simply open a customer's live location or camera feed:
 * they must hold an explicit, time-boxed, justified grant, and every use of it
 * is counted and audited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_access_grants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            // Why access was granted. Mandatory: there is no "just looking".
            $table->text('reason');
            $table->string('reference')->nullable();
            // Which sensitive capabilities are covered: location, video, voice,
            // documents, commands.
            $table->json('scopes');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->unsignedInteger('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'expires_at']);
            $table->index(['vehicle_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_access_grants');
    }
};
