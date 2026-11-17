<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();

            $table->enum('document_type', [
                'formulir_peminjaman',
                'bast_peminjaman',
                'formulir_pengembalian',
                'bast_pengembalian',
            ]);

            $table->string('file_path'); // Path di private storage (disk 'local')
            $table->string('sha256_hash', 64); // Hash SHA-256 dari biner PDF sebelum enkripsi
            $table->string('encryption_cipher', 50)->default('AES-256-CBC');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->json('signers_snapshot')->nullable(); // Rekam jejak penanda tangan saat dokumen dikunci
            $table->timestamp('finalized_at')->useCurrent();
            $table->timestamps();

            $table->unique(['booking_id', 'document_type'], 'unique_booking_final_document');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_documents');
    }
};