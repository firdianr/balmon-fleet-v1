<?php

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Helper membuat user sesuai peran dengan atribut lengkap
 */
function createUserWithRole(string $role): User
{
    return User::factory()->create([
        'slug'              => $role . '-' . Str::random(5),
        'nip'               => fake()->unique()->numerify('199###############'),
        'role'              => $role,
        'phone'             => '081234567890',
        'email_verified_at' => now(),
    ]);
}

/**
 * Helper membuat data booking minimal untuk uji akses rute berparameter {booking:letter_slug}
 */
function createDummyBooking(User $owner): Booking
{
    return Booking::create([
        'user_id'       => $owner->id,
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-TEST/' . Str::random(5),
        'letter_slug'   => 'st-test-' . strtolower(Str::random(6)),
        'letter_file'   => 'letters/dummy.pdf', // <-- Tambahkan ini
        'vehicle_type'  => 'operational',
        'start_date'    => now()->addDay(),
        'end_date'      => now()->addDays(2),
        'destination'   => 'Semarang',
        'purpose'       => 'Uji Akses RBAC',
        'participants'  => [],
        'status'        => 'pending',
    ]);
}

it('mengizinkan seluruh 5 peran yang terautentikasi mengakses dashboard dan katalog kendaraan', function (string $role) {
    $user = createUserWithRole($role);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('vehicles.index'))->assertOk();
})->with(['pegawai', 'driver', 'admin_lap', 'approver', 'admin_app']);

it('menolak akses manajemen pengguna (admin_app) bagi selain admin_app dengan 403 Forbidden', function (string $role) {
    $user = createUserWithRole($role);

    $this->actingAs($user)
        ->get(route('admin_app.users.index'))
        ->assertForbidden();
})->with(['pegawai', 'driver', 'admin_lap', 'approver']);

it('mengizinkan admin_app mengakses halaman manajemen pengguna', function () {
    $adminApp = createUserWithRole('admin_app');

    $this->actingAs($adminApp)
        ->get(route('admin_app.users.index'))
        ->assertOk();
});

it('menolak akses pengelolaan armada admin dan alokasi booking bagi pegawai, driver, dan approver', function (string $role) {
    $user    = createUserWithRole($role);
    $pegawai = createUserWithRole('pegawai');
    $booking = createDummyBooking($pegawai);

    $this->actingAs($user)
        ->get(route('admin_app.vehicles.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('bookings.process_review', $booking), ['action' => 'approve'])
        ->assertForbidden();
})->with(['pegawai', 'driver', 'approver']);

it('menolak akses persetujuan pejabat (approver) bagi pegawai, driver, dan admin_lap', function (string $role) {
    $user    = createUserWithRole($role);
    $pegawai = createUserWithRole('pegawai');
    $booking = createDummyBooking($pegawai);

    $this->actingAs($user)
        ->patch(route('bookings.approver_review', $booking), ['action' => 'approve'])
        ->assertForbidden();
})->with(['pegawai', 'driver', 'admin_lap']);

it('menolak akses pengisian log perjalanan (start-trip) bagi approver', function () {
    $approver = createUserWithRole('approver');
    $pegawai  = createUserWithRole('pegawai');
    $booking  = createDummyBooking($pegawai);

    $this->actingAs($approver)
        ->patch(route('bookings.start_trip', $booking))
        ->assertForbidden();
});