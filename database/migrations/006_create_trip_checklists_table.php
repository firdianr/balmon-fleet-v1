<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('vehicle_log_id')->nullable()->constrained('vehicle_logs')->nullOnDelete();
            
            // Tipe Checklist: 'start' = Keberangkatan, 'end' = Kepulangan
            $table->enum('type', ['start', 'end']);
            
            // Menyimpan snapshot array JSON checklist:
            // [{"id": 1, "name": "Bodi & Cat", "category": "condition", "is_checked": true}, ...]
            $table->json('items');

            // Khusus Pengembalian Multi-Day (Cuci Mobil)
            $table->boolean('is_washed')->default(true)->comment('Status Kebersihan / Cuci Mobil');
            $table->string('wash_receipt_photo')->nullable()->comment('Foto Nota / Bukti Cuci Mobil');

            // Catatan Opsional jika ada bagian yang rusak/bermasalah
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_checklists');
    }
};