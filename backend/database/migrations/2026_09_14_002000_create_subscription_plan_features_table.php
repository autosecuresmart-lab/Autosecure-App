<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan -> feature entitlements.
 *
 * Every entitlement is resolved server side. Free plans always carry the core
 * security + dashcam features; that is a product rule, not a UI concern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plan_features', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            // Matches a key in config('autosecure.entitlements').
            $table->string('feature_key', 128);
            // Null = enabled/boolean. Otherwise a numeric or JSON limit.
            $table->string('value')->nullable();
            $table->timestamps();

            // The auto-generated name would be
            // `subscription_plan_features_subscription_plan_id_feature_key_unique`
            // at 66 characters, which exceeds MySQL's 64-character identifier
            // limit. SQLite does not enforce that limit, so an explicit short
            // name is required for the migration to work on MySQL.
            $table->unique(['subscription_plan_id', 'feature_key'], 'subscription_plan_features_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_features');
    }
};
