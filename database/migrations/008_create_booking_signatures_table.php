<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_signatures', function (Blueprint $table) {
            $table->id();

            // Relasi Ke Booking & User
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Membedakan 4 Dokumen & 3 Peran Penandatangan
            $table->enum('document_type', [
                'formulir_peminjaman',
                'bast_peminjaman',
                'formulir_pengembalian',
                'bast_pengembalian'
            ]);

            $table->enum('signer_role', [
                'pemohon',
                'admin_lap',
                'approver'
            ]);

            // Data Coretan TTD (Path ke File)
            $table->string('signature_path');

            // Waktu Penandatanganan & Audit Trail
            $table->timestamp('signed_at')->useCurrent();
            $table->timestamps();

            // Mencegah duplikasi TTD untuk peran & jenis dokumen yang sama pada 1 booking
            $table->unique(['booking_id', 'document_type', 'signer_role'], 'unique_booking_doc_signature');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_signatures');
    }
};