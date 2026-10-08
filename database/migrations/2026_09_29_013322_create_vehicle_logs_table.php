<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicle_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            
            // Check-Out Data (Keberangkatan)
            $table->dateTime('checked_out_at');
            $table->integer('start_km');
            $table->string('start_fuel_level', 20); // Sisa BBM saat Check-Out
            $table->string('start_photo')->nullable();

            // Check-In Data (Pengembalian)
            $table->dateTime('checked_in_at')->nullable();
            $table->integer('end_km')->nullable();
            $table->string('end_fuel_level', 20)->nullable(); // Sisa BBM saat Check-In
            $table->string('end_photo')->nullable();
            $table->integer('distance_traveled')->nullable();
            $table->text('condition_notes')->nullable();
            $table->json('condition_photos')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_logs');
    }
};
