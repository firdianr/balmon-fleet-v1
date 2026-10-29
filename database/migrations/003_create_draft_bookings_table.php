<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draft_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Tipe & Alokasi Armada/Driver
            $table->enum('vehicle_type', ['operational', 'monitoring']);
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Detail Perjalanan Informal
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('destination');
            $table->text('purpose');
            $table->text('vehicle_requirements')->nullable();
            $table->json('participants')->nullable();
            
            // Status Layer 1
            // draft: diajukan pegawai | approved: disetujui admin lap | rejected: ditolak | converted: sudah di-upload surat tugas & masuk ke booking resmi
            $table->enum('status', ['draft', 'approved', 'rejected', 'converted'])->default('draft');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draft_bookings');
    }
};