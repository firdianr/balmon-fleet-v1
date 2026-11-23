<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    Http::fake([
        '*/send-message' => Http::response(['status' => true], 200),
    ]);
});

function fakeSignature(): string
{
    return 'data:image/png;base64,' . base64_encode('fake-png-signature-content');
}

function createVehicle(array $overrides = []): Vehicle
{
    return Vehicle::create(array_merge([
        'plate_number' => 'H ' . rand(1000, 9999) . ' XA',
        'slug'         => 'mobil-' . uniqid(),
        'brand'        => 'Toyota',
        'model'        => 'Innova Zenix',
        'code'         => 'MON-' . rand(10, 99),
        'type'         => 'monitoring',
        'drivetrain'   => '2wd',
        'capacity'     => 7,
        'fuel_type'    => 'Solar',
        'status'       => 'available',
    ], $overrides));
}

it('TC-F03: mengekstrak data surat tugas via Gemini AI dan mencocokkan NIP peserta', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai', 'nip' => '199203152018021002']);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'letter_type'   => 'surat_tugas',
                            'letter_number' => 'ST-123/BALMON/2026',
                            'destination'   => 'Kabupaten Pati',
                            'purpose'       => 'Monitoring Frekuensi Radio',
                            'start_date'    => now()->addDays(2)->format('Y-m-d 08:00:00'),
                            'end_date'      => now()->addDays(3)->format('Y-m-d 17:00:00'),
                            'nips'          => ['199203152018021002'],
                        ])
                    ]]
                ]
            ]]
        ], 200),
    ]);

    $response = $this->actingAs($pegawai)->postJson(route('bookings.parse_document'), [
        'document' => UploadedFile::fake()->create('surat_tugas.pdf', 500, 'application/pdf'),
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.letter_number', 'ST-123/BALMON/2026')
        ->assertJsonPath('data.destination', 'Kabupaten Pati')
        ->assertJsonPath('data.user_ids.0', (string) $pegawai->id);
});

it('TC-F03b: mengembalikan respon 422 terkendali saat Gemini API mengalami timeout (fallback manual)', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response('Service Unavailable', 503),
    ]);

    $response = $this->actingAs($pegawai)->postJson(route('bookings.parse_document'), [
        'document' => UploadedFile::fake()->create('surat_tugas.pdf', 500, 'application/pdf'),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('TC-F05: menyimpan pengajuan baru beserta tanda tangan digital privat dan audit trail', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $adminLap = User::factory()->create(['role' => 'admin_lap']);
    $vehicle = createVehicle(['type' => 'monitoring']);

    $response = $this->actingAs($pegawai)->post(route('bookings.store'), [
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-001/BALMON-SMG/2026',
        'letter_file'   => UploadedFile::fake()->create('st.pdf', 1024, 'application/pdf'),
        'vehicle_type'  => 'monitoring',
        'vehicle_id'    => $vehicle->id,
        'start_date'    => now()->addDays(2)->format('Y-m-d 08:00:00'),
        'end_date'      => now()->addDays(3)->format('Y-m-d 17:00:00'),
        'destination'   => 'Kabupaten Pati',
        'purpose'       => 'Monitoring Spektrum Frekuensi',
        'participants'  => [],
        'signature'     => fakeSignature(),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('bookings.index'));

    $booking = Booking::first();
    expect($booking)->not->toBeNull();
    expect($booking->status)->toBe('pending');

    $this->assertDatabaseHas('booking_signatures', [
        'booking_id'    => $booking->id,
        'user_id'       => $pegawai->id,
        'document_type' => 'formulir_peminjaman',
        'signer_role'   => 'pemohon',
    ]);
});

it('TC-F04: menolak pengajuan apabila jadwal kendaraan bentrok dengan transaksi aktif lain', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $vehicle = createVehicle(['type' => 'monitoring']);

    Booking::create([
        'user_id'       => $pegawai->id,
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-EXISTING/2026',
        'letter_slug'   => 'st-existing-2026',
        'letter_file'   => 'letters/dummy.pdf',
        'vehicle_type'  => 'monitoring',
        'vehicle_id'    => $vehicle->id,
        'start_date'    => now()->addDays(2)->format('Y-m-d 08:00:00'),
        'end_date'      => now()->addDays(4)->format('Y-m-d 17:00:00'),
        'destination'   => 'Kudus',
        'purpose'       => 'Tugas Lapangan',
        'participants'  => [],
        'status'        => 'approved',
    ]);

    $response = $this->actingAs($pegawai)->post(route('bookings.store'), [
        'letter_type'   => 'nota_dinas',
        'letter_number' => 'ND-BENTROK/2026',
        'letter_file'   => UploadedFile::fake()->create('nd.pdf', 500, 'application/pdf'),
        'vehicle_type'  => 'monitoring',
        'vehicle_id'    => $vehicle->id,
        'start_date'    => now()->addDays(3)->format('Y-m-d 09:00:00'),
        'end_date'      => now()->addDays(3)->format('Y-m-d 15:00:00'),
        'destination'   => 'Demak',
        'purpose'       => 'Inspeksi',
        'signature'     => fakeSignature(),
    ]);

    $response->assertSessionHasErrors('vehicle_id');
    expect(Booking::count())->toBe(1);
});

it('TC-NF03 & TC-NF04: tetap menyimpan transaksi saat WA Gateway mati dan menolak unggahan non-PDF', function () {
    Http::fake([
        '*/send-message' => Http::response(null, 500),
    ]);

    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $vehicle = createVehicle(['type' => 'monitoring']);

    // Uji unggahan non-PDF (TC-NF04)
    $invalidFileResponse = $this->actingAs($pegawai)->post(route('bookings.store'), [
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-INVALID-FILE/2026',
        'letter_file'   => UploadedFile::fake()->create('virus.exe', 500),
        'vehicle_type'  => 'monitoring',
        'vehicle_id'    => $vehicle->id,
        'start_date'    => now()->addDays(5)->format('Y-m-d 08:00:00'),
        'end_date'      => now()->addDays(5)->format('Y-m-d 16:00:00'),
        'destination'   => 'Semarang',
        'purpose'       => 'Uji Keamanan',
        'signature'     => fakeSignature(),
    ]);
    $invalidFileResponse->assertSessionHasErrors('letter_file');

    // Uji graceful fallback saat WA mati (TC-NF03)
    $validResponse = $this->actingAs($pegawai)->post(route('bookings.store'), [
        'letter_type'   => 'surat_tugas',
        'letter_number' => 'ST-FALLBACK-WA/2026',
        'letter_file'   => UploadedFile::fake()->create('valid.pdf', 500, 'application/pdf'),
        'vehicle_type'  => 'monitoring',
        'vehicle_id'    => $vehicle->id,
        'start_date'    => now()->addDays(5)->format('Y-m-d 08:00:00'),
        'end_date'      => now()->addDays(5)->format('Y-m-d 16:00:00'),
        'destination'   => 'Semarang',
        'purpose'       => 'Uji Fallback WA',
        'signature'     => fakeSignature(),
    ]);

    $validResponse->assertSessionHasNoErrors();
    $this->assertDatabaseHas('bookings', ['letter_number' => 'ST-FALLBACK-WA/2026', 'status' => 'pending']);
});