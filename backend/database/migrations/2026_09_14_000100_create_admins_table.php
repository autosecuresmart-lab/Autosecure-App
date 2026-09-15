<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AUTOSECURE staff accounts.
 *
 * Deliberately a SEPARATE table from `users`. Customers and staff have
 * different lifecycles, different guards and must never be able to authenticate
 * against each other's surface.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 32)->nullable();
            $table->string('password');
            $table->string('job_title')->nullable();
            $table->string('avatar_path')->nullable();
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active');
            // Break-glass flag: bypasses permission checks. Expected to be used
            // by at most a handful of accounts and is fully audited.
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('two_factor_secret', 255)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
