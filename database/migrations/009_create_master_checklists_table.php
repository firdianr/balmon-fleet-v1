<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: STNK, AC, Ban Serep
            $table->enum('category', ['condition', 'completeness'])->comment('condition = Kondisi Fisik, completeness = Kelengkapan Dinas');
            $table->boolean('is_active')->default(true)->comment('Status aktif item checklist');
            $table->integer('sort_order')->default(0)->comment('Urutan tampilan di form');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_checklists');
    }
};