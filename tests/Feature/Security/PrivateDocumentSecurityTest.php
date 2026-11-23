<?php

use App\Models\Booking;
use App\Models\User;
use App\Services\DocumentArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('TC-NF01: menolak akses unduh dokumen PDF bagi pengguna belum login dan saat status belum memenuhi syarat', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $booking = Booking::create([
        'user_id'       => $pegawai->id,
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-SEC-01/2026',
        'letter_slug'   => 'st-sec-01-2026',
        'letter_file'   => 'letters/dummy.pdf',
        'vehicle_type'  => 'operational',
        'start_date'    => now()->addDay(),
        'end_date'      => now()->addDays(2),
        'destination'   => 'Semarang',
        'purpose'       => 'Uji Keamanan Dokumen',
        'participants'  => [],
        'status'        => 'pending',
    ]);

    // 1. Pengunjung tanpa login (guest) harus diarahkan ke halaman login (302)
    $this->get(route('bookings.pdf.permohonan', $booking))
        ->assertRedirect(route('login'));

    // 2. BAST Peminjaman & BAST Pengembalian tidak boleh dicetak saat status masih 'pending' (403)
    $this->actingAs($pegawai)
        ->get(route('bookings.pdf.bast_peminjaman', $booking))
        ->assertForbidden();

    $this->actingAs($pegawai)
        ->get(route('bookings.pdf.bast_pengembalian', $booking))
        ->assertForbidden();
});

it('TC-NF02: mengenkripsi PDF ke private storage, memverifikasi kecocokan hash SHA-256, dan mendeteksi manipulasi data', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $booking = Booking::create([
        'user_id'       => $pegawai->id,
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-HASH-01/2026',
        'letter_slug'   => 'st-hash-01-2026',
        'letter_file'   => 'letters/dummy.pdf',
        'vehicle_type'  => 'operational',
        'start_date'    => now()->addDay(),
        'end_date'      => now()->addDays(2),
        'destination'   => 'Pati',
        'purpose'       => 'Uji Integritas SHA-256',
        'participants'  => [],
        'status'        => 'completed',
    ]);

    // 1. Arsipkan dokumen final ke private storage (AES-256-CBC + SHA-256)
    $archivedDoc = DocumentArchiveService::archiveDocument($booking, 'formulir_peminjaman');

    expect(Storage::disk('local')->exists($archivedDoc->file_path))->toBeTrue();
    expect($archivedDoc->encryption_cipher)->toBe('AES-256-CBC');

    // 2. Dekripsi dan pastikan hash SHA-256 cocok 100%
    $decryptedBytes = DocumentArchiveService::getVerifiedDecryptedPdf($archivedDoc);
    expect(hash('sha256', $decryptedBytes))->toBe($archivedDoc->sha256_hash);

    // 3. Simulasikan manipulasi hash pada database -> harus memicu RuntimeException keamanan
    $archivedDoc->update(['sha256_hash' => str_repeat('0', 64)]);

    expect(fn () => DocumentArchiveService::getVerifiedDecryptedPdf($archivedDoc))
        ->toThrow(\RuntimeException::class, 'Integritas file PDF tidak valid');
});