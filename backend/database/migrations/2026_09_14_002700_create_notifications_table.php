<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notification records (the customer's notification centre) plus the
 * delivery state of push/email/SMS copies.
 *
 * NOTE: this is AUTOSECURE's own table, not Laravel's default `notifications`
 * schema, because every AUTOSECURE table carries both `id` and `uuid`. The
 * database notification channel is therefore not used; notifications are
 * written through App\Services\Notifications\NotificationService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Exactly one of these is set, depending on who is notified.
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->cascadeOnDelete();
            // Optional polymorphic link to whatever caused the notification.
            $table->nullableMorphs('subject');
            $table->string('type', 64);
            $table->string('category', 64)->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->enum('channel', ['in_app', 'push', 'email', 'sms'])->default('in_app');
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'read'])->default('pending');
            $table->json('data')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['admin_id', 'read_at']);
            $table->index(['status', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
