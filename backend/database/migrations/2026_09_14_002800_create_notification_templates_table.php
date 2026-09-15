<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable notification/email templates.
 *
 * Bodies are rendered with a whitelisted token replacement only — template
 * content is never evaluated as code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('key', 128)->unique();
            $table->string('name');
            $table->enum('channel', ['in_app', 'push', 'email', 'sms'])->default('push');
            $table->string('subject')->nullable();
            $table->text('body');
            // Documented list of tokens the body may use, e.g. ["name","vehicle"].
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['channel', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
