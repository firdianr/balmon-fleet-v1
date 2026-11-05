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

            // Relasi ke Draft Request (Layer 1)
            $table->foreignId('draft_booking_id')->nullable()->constrained('draft_bookings')->nullOnDelete();

            // Berkas & Nomor Dokumen Awal
            $table->enum('letter_type', ['surat_tugas', 'nota_dinas'])->default('surat_tugas'); // <-- BARU: Tipe Surat
            $table->string('letter_number', 100); 
            $table->string('letter_slug', 120)->unique();
            $table->string('letter_file'); // Path PDF Dokumen Awal (Surat Tugas / Nota Dinas)
            
            // Berkas Susulan Pengembalian (Wajib jika letter_type = nota_dinas)
            $table->string('return_letter_number', 100)->nullable(); 
            $table->string('return_letter_file')->nullable(); // <-- BARU: PDF Surat Tugas Susulan saat pengembalian
            
            // Nomor Berita Acara Serah Terima (BAST)
            $table->string('bast_peminjaman', 100)->nullable(); 
            $table->string('bast_pengembalian', 100)->nullable(); 

            // Pemohon, Unit Armada, & Driver
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->cascadeOnDelete();
            $table->enum('vehicle_type', ['operational', 'monitoring'])->default('operational');
            $table->text('vehicle_requirements')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();

            // Detail Jadwal & Perjalanan
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('destination');
            $table->text('purpose');
            $table->json('participants')->nullable(); 
            $table->enum('status', ['pending', 'waiting', 'approved', 'rejected', 'on_trip', 'returned', 'checked', 'completed', 'canceled'])->default('pending');
            $table->text('admin_note')->nullable();

            // Audit Trail Alur Verifikasi
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('returned_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_checked_at')->nullable();

            $table->foreignId('returned_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_approved_at')->nullable();
            
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
