<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fuel usage log (proposal section 03). Feeds the consumption and spending
 * summary on the vehicle dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('filled_at');
            $table->decimal('litres', 8, 2);
            $table->decimal('price_per_litre', 10, 2)->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->unsignedBigInteger('odometer_km')->nullable();
            $table->boolean('is_full_tank')->default(true);
            $table->string('station')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'filled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_records');
    }
};
