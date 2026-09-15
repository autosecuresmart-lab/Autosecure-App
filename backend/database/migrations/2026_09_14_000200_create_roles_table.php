<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles (e.g. Super Admin, Operations, Finance, Vendor Officer, Support Agent).
 *
 * `guard` keeps staff roles and any future customer roles in the same table
 * without them ever being interchangeable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('guard', 32)->default('admin');
            $table->string('description')->nullable();
            // Seeded roles cannot be deleted from the admin portal by accident.
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index('guard');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
