<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_attendances', function (Blueprint $table) {
            $table->id();
            $table->index(['process_status', 'end_date']);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['cuti', 'sakit', 'izin', 'service', 'non_active'])->default('cuti');
            
            // Kolom Alur Persetujuan (Approval Workflow)
            $table->enum('process_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('attachment_file')->nullable(); // File Bukti Cuti / Surat Dokter (PDF/JPG)
            
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->text('notes')->nullable();
            
            // Log Pembuat & Pengesah
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('action_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('action_at')->nullable();
            $table->text('rejection_note')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_attendances');
    }
};