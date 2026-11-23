<?php

use App\Mail\BookingCompletedMail;
use App\Models\Booking;
use App\Models\BookingDocument;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Mail::fake();
    Http::fake(['*/send-message' => Http::response(['status' => true], 200)]);
});

it('TC-F06 s.d. TC-F09 & TC-NF02: menjalankan siklus penuh dari pending hingga completed dan memverifikasi hash SHA-256', function () {
    $pegawai  = User::factory()->create(['role' => 'pegawai', 'email' => 'bangfiremulator@gmail.com']);
    $adminLap = User::factory()->create(['role' => 'admin_lap']);
    $approver = User::factory()->create(['role' => 'approver']);
    $driver   = User::factory()->create(['role' => 'driver']);

    $vehicle = Vehicle::create([
        'plate_number' => 'H 1234 BM',
        'slug'         => 'innova-bm',
        'brand'        => 'Toyota',
        'model'        => 'Innova',
        'code'         => 'OP-01',
        'type'         => 'operational',
        'drivetrain'   => '2wd',
        'capacity'     => 7,
        'fuel_type'    => 'Solar',
        'status'       => 'available',
    ]);

    $sig = 'data:image/png;base64,' . base64_encode('signature-png-data');

    // Struktur JSON checklist sesuai format asli aplikasi (name, category, is_checked)
    $checklistItems = json_encode([
        [
            'id'         => 1,
            'name'       => 'Kondisi Body & Cat Kendaraan',
            'category'   => 'condition',
            'is_checked' => true,
        ],
        [
            'id'         => 2,
            'name'       => 'STNK & Kelengkapan Kendaraan',
            'category'   => 'completeness',
            'is_checked' => true,
        ],
    ]);

    // 1. Pengajuan oleh Pegawai (status: pending)
    $this->actingAs($pegawai)->post(route('bookings.store'), [
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-FULL-CYCLE/2026',
        'letter_file'   => UploadedFile::fake()->create('st.pdf', 500, 'application/pdf'),
        'vehicle_type'  => 'operational',
        'start_date'    => now()->addDays(1)->format('Y-m-d 08:00:00'),
        'end_date'      => now()->addDays(1)->format('Y-m-d 16:00:00'),
        'destination'   => 'Surakarta',
        'purpose'       => 'Pengukuran Spektrum',
        'signature'     => $sig,
    ])->assertSessionHasNoErrors();

    $booking = Booking::first();
    expect($booking->status)->toBe('pending');

    // 2. Alokasi oleh Admin Lapangan (pending -> waiting)
    $this->actingAs($adminLap)->patch(route('bookings.process_review', $booking), [
        'action'     => 'approve',
        'vehicle_id' => $vehicle->id,
        'driver_id'  => $driver->id,
        'signature'  => $sig,
    ])->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('waiting');

    // 3. Persetujuan oleh Approver (waiting -> approved)
    $this->actingAs($approver)->patch(route('bookings.approver_review', $booking), [
        'action'    => 'approve',
        'signature' => $sig,
    ])->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('approved');

    // 4. Inspeksi Keberangkatan oleh Driver (approved -> trip_started)
    $this->actingAs($driver)->patch(route('bookings.start_trip', $booking), [
        'start_fuel_level' => 'Full (100%)',
        'start_photo'      => UploadedFile::fake()->image('start.jpg'),
        'items'            => $checklistItems,
    ])->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('trip_started');

    // 5. TTD Serah Terima Keberangkatan oleh Pemohon (trip_started -> on_trip)
    $this->actingAs($pegawai)->patch(route('bookings.sign_departure', $booking), [
        'signature' => $sig,
    ])->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('on_trip');

    // 6. Inspeksi Kepulangan oleh Driver (on_trip -> trip_completed)
    $this->actingAs($driver)->patch(route('bookings.complete_trip', $booking), [
        'end_fuel_level' => '75%',
        'end_photo'      => UploadedFile::fake()->image('end.jpg'),
        'items'          => $checklistItems,
    ])->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('trip_completed');

    // 7. TTD Serah Terima Kepulangan oleh Pemohon (trip_completed -> returned)
    $this->actingAs($pegawai)->patch(route('bookings.sign_return', $booking), [
        'signature' => $sig,
    ])->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('returned');

    // 8. Verifikasi Fisik Kepulangan oleh Admin Lapangan (returned -> checked)
    $this->actingAs($adminLap)->patch(route('bookings.verify_return', $booking), [
        'signature'                => $sig,
        'items'                    => $checklistItems,
        'is_washed'                => true,
        'admin_verification_notes' => 'Armada bersih dan lengkap',
    ])->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('checked');

    // 9. Pengesahan Akhir oleh Approver (checked -> completed)
    $this->actingAs($approver)->patch(route('bookings.approver_review', $booking), [
        'action'    => 'approve',
        'signature' => $sig,
    ])->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('completed');
    expect($vehicle->fresh()->status)->toBe('available');

    // Verifikasi Arsip Email dikirim ke bangfiremulator@gmail.com & Integritas Hash SHA-256 (TC-NF02)
    Mail::assertQueued(BookingCompletedMail::class, function ($mail) {
        return $mail->hasTo('bangfiremulator@gmail.com');
    });

    $bastDoc = BookingDocument::where('booking_id', $booking->id)
        ->where('document_type', 'bast_pengembalian')
        ->first();

    expect($bastDoc)->not->toBeNull();
    expect($bastDoc->sha256_hash)->toHaveLength(64);
});

it('TC-F07: mengizinkan pemohon membatalkan pengajuan yang masih berstatus pending', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $booking = Booking::create([
        'user_id'       => $pegawai->id,
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-CANCEL/2026',
        'letter_slug'   => 'st-cancel-2026',
        'letter_file'   => 'letters/dummy.pdf',
        'vehicle_type'  => 'operational',
        'start_date'    => now()->addDays(2),
        'end_date'      => now()->addDays(3),
        'destination'   => 'Kendal',
        'purpose'       => 'Uji Pembatalan',
        'participants'  => [],
        'status'        => 'pending',
    ]);

    $this->actingAs($pegawai)
        ->patch(route('bookings.cancel', $booking))
        ->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe('canceled');
});