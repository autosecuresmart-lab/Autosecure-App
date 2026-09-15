<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable platform settings (commission, coin rates, grace period,
 * retention, feature toggles...).
 *
 * Config files hold the safe defaults; this table holds the values the business
 * changes at runtime without a deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('group', 64)->default('general');
            $table->string('key', 128)->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'decimal', 'boolean', 'json'])->default('string');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            // Public settings may be exposed to the mobile app unauthenticated.
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
