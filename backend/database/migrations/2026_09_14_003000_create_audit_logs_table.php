<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail.
 *
 * Records who did what, to which record, from where. Used for customer actions,
 * staff actions, device commands and sensitive data access (location, video,
 * voice, documents).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Actor is a customer, an admin, the system, or a device webhook.
            $table->nullableMorphs('actor');
            $table->string('actor_label')->nullable();
            // The affected record.
            $table->nullableMorphs('auditable');
            $table->string('action', 128);
            $table->string('group', 64)->nullable();
            $table->text('description')->nullable();
            $table->json('changes')->nullable();
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->enum('severity', ['info', 'notice', 'warning', 'critical'])->default('info');
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['group', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
