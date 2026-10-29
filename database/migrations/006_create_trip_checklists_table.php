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
            
            // Checklist Kondisi Fisik (true = Baik / Normal, false = Ada Catatan)
            $table->boolean('body_condition')->default(true)->comment('Bodi & Cat (Mulus / Baret / Penyok)');
            $table->boolean('tires_condition')->default(true)->comment('Ban Utama & Cadangan');
            $table->boolean('lights_condition')->default(true)->comment('Lampu Utama, Sein, Rem & Hazard');
            $table->boolean('glass_mirrors')->default(true)->comment('Kaca Depan, Samping & Spion');
            $table->boolean('brakes_condition')->default(true)->comment('Rem Utama & Handbrake');
            $table->boolean('interior_cleanliness')->default(true)->comment('Kebersihan Kabin & Jok');
            
            // Kelengkapan Dinas Wajib
            $table->boolean('has_stnk')->default(true)->comment('Fisik STNK Kendaraan');
            $table->boolean('has_spare_tire')->default(true)->comment('Ban Serep');
            $table->boolean('has_jack_tools')->default(true)->comment('Dongkrak & Kunci Roda');
            $table->boolean('has_first_aid')->default(true)->comment('Kotak P3K & Segitiga Pengaman');

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