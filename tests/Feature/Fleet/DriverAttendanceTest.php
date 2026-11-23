<?php

use App\Models\Booking;
use App\Models\DriverAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(['*/send-message' => Http::response(['status' => true], 200)]);
});

it('TC-F02: mencatat ketidakhadiran driver, menyetujuinya, dan mengirim dataset ke tampilan alokasi', function () {
    $driver   = User::factory()->create(['role' => 'driver']);
    $adminLap = User::factory()->create(['role' => 'admin_lap']);
    $pegawai  = User::factory()->create(['role' => 'pegawai']);

    // 1. Driver mengajukan cuti (status awal: pending)
    $this->actingAs($driver)->post(route('drivers.attendance.store'), [
        'user_id'    => $driver->id,
        'status'     => 'cuti',
        'start_date' => now()->addDays(2)->format('Y-m-d'),
        'end_date'   => now()->addDays(4)->format('Y-m-d'),
        'notes'      => 'Cuti tahunan',
    ])->assertSessionHasNoErrors();

    $attendance = DriverAttendance::first();
    expect($attendance->process_status)->toBe('pending');

    // 2. Admin Lapangan menyetujui pengajuan cuti (process_status -> approved)
    $this->actingAs($adminLap)->patch(route('drivers.attendance.approve', $attendance->id), [
        'process_status' => 'approved',
    ])->assertSessionHasNoErrors();

    expect($attendance->fresh()->process_status)->toBe('approved');

    // 3. Pastikan dataset attendances yang disetujui muncul pada halaman detail/alokasi booking
    $booking = Booking::create([
        'user_id'       => $pegawai->id,
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-ATT-CHECK/2026',
        'letter_slug'   => 'st-att-check-2026',
        'letter_file'   => 'letters/dummy.pdf',
        'vehicle_type'  => 'operational',
        'start_date'    => now()->addDays(3)->format('Y-m-d 08:00:00'),
        'end_date'      => now()->addDays(3)->format('Y-m-d 16:00:00'),
        'destination'   => 'Magelang',
        'purpose'       => 'Uji Filter Driver',
        'participants'  => [],
        'status'        => 'pending',
    ]);

    $this->actingAs($adminLap)
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertViewHas('attendances', function ($attendances) use ($driver) {
            return collect($attendances)->contains('driver_id', $driver->id);
        });
});