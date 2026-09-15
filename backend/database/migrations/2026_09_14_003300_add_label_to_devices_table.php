<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-facing device label.
 *
 * The devices table already carries brand, model and serial number, which are
 * provisioning facts. A customer with two dashcams (or a tracker plus a dashcam
 * on the same vehicle) needs to tell them apart, and the dashcam device settings
 * experience in the proposal includes "device name".
 *
 * This is the only schema change Phase 2 requires; every other table already
 * existed from the Phase 1 foundation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('label', 120)->nullable()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
