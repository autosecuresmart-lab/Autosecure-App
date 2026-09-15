<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vehicle sharing.
 *
 * Ownership and sharing are enforced server side: a request only reaches a
 * vehicle when the acting user has an active grant on it. Sensitive
 * capabilities (location, video, device commands) are granted individually so
 * that a "driver" cannot silently watch the owner's camera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_access_grants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('role', ['owner', 'driver', 'viewer'])->default('viewer');
            $table->boolean('can_view_location')->default(true);
            $table->boolean('can_view_video')->default(false);
            $table->boolean('can_send_commands')->default(false);
            $table->boolean('can_manage_care_records')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'user_id']);
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_access_grants');
    }
};
