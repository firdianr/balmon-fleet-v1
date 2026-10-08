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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('letter_number', 100); // Nomor Surat Perintah (Ganti booking_code)
            $table->string('letter_slug', 120)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('destination');
            $table->text('purpose');
            $table->string('letter_file'); // Path file PDF Surat Perintah
            $table->json('participants')->nullable(); // Array JSON anggota tim
            $table->enum('status', ['pending', 'approved', 'rejected', 'on_trip', 'unconfirmed', 'completed', 'canceled'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('departed_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('departed_at')->nullable();
            $table->foreignId('returned_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
