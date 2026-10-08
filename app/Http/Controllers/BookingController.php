<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $bookings = Booking::with(['user', 'vehicle', 'log'])
            ->when($user->role !== 'admin', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        // 1. Ambil semua vehicle_id unik dari item booking di halaman aktif
        $vehicleIds = $bookings->getCollection()->pluck('vehicle_id')->filter()->unique();

        // 2. Ambil log terakhir untuk setiap kendaraan dalam 1 query saja
        $latestLogs = VehicleLog::whereIn('vehicle_id', $vehicleIds)
            ->select('vehicle_id', 'start_km', 'end_km')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('vehicle_logs')
                    ->groupBy('vehicle_id');
            })
            ->get()
            ->keyBy('vehicle_id');

        // 3. Map/Transform nilai ke objek vehicle pada setiap booking
        $bookings->getCollection()->transform(function ($booking) use ($latestLogs) {
            if ($booking->vehicle) {
                $lastLog = $latestLogs->get($booking->vehicle_id);

                // Variabel 1: Odometer / KM Terakhir armada saat ini (Fallback ke start_km jika belum end_km)
                $booking->vehicle->last_km = $lastLog 
                    ? ($lastLog->end_km ?? $lastLog->start_km ?? 0) 
                    : 0;

                // Variabel 2: Murni end_km dari log terakhir (terpisah, null jika belum selesai/tidak ada)
                $booking->vehicle->last_log_end_km = $lastLog ? $lastLog->end_km : null;
            }
            return $booking;
        });

        return view('bookings.index', compact('bookings'));
    }

    public function create(Request $request)
    {
        // Ambil daftar mobil beserta jadwal aktifnya
        $vehicles = Vehicle::with(['bookings' => function ($query) {
            $query->whereIn('status', ['approved', 'on_trip'])
                ->where('end_date', '>=', now())
                ->orderBy('start_date', 'asc');
        }])->get();

        // Ambil daftar seluruh pegawai untuk pilihan anggota tim
        $employees = User::where('id', '!=', Auth::id())
            ->orderBy('name', 'asc')
            ->get();

        // Tangkap slug mobil jika diklik dari halaman Daftar Armada (misal: ?vehicle=h-1234-ab)
        $selectedVehicleSlug = $request->query('vehicle');

        return view('bookings.create', compact('vehicles', 'employees', 'selectedVehicleSlug'));
    }

    public function store(StoreBookingRequest $request)
    {
        $validated = $request->validated();

        // 1. Penanganan Upload File Surat Perintah (PDF)
        if ($request->hasFile('letter_file')) {
            $validated['letter_file'] = $request->file('letter_file')->store('letters', 'public');
        } elseif ($request->filled('letter_file_temp')) {
            // Gunakan file PDF yang terunggah dari percobaan sebelumnya
            $validated['letter_file'] = $request->input('letter_file_temp');
        }

        // 2. Filter Array Anggota Tim (dibersihkan dari nilai null/kosong lebih awal)
        if (isset($validated['participants'])) {
            $validated['participants'] = array_values(array_filter($validated['participants']));
        }

        // ------------------------------------------------------------------
        // A. CEK BENTROK JADWAL KENDARAAN
        // ------------------------------------------------------------------
        $isVehicleOverlapped = Booking::where('vehicle_id', $validated['vehicle_id'])
            ->whereIn('status', ['approved', 'on_trip', 'unconfirmed'])
            ->where(function ($query) use ($validated) {
                $query->where('start_date', '<', $validated['end_date'])
                    ->where('end_date', '>', $validated['start_date']);
            })
            ->exists();

        if ($isVehicleOverlapped) {
            return back()
                ->withInput($request->except('letter_file') + ['letter_file_temp' => $validated['letter_file'] ?? null])
                ->withErrors([
                    'vehicle_id' => 'Mobil ini sudah memiliki jadwal peminjaman yang disetujui pada jam/tanggal tersebut.'
                ]);
        }

        // ------------------------------------------------------------------
        // B. CEK BENTROK JADWAL PEMINJAM UTAMA & ANGGOTA TIM
        // ------------------------------------------------------------------
        // Kumpulkan seluruh ID personil yang terlibat (Peminjam + Anggota Tim)
        $allPersonnelIds = array_merge([Auth::id()], $validated['participants'] ?? []);

        // Cek apakah ada personil yang sudah terdaftar di peminjaman 'approved' / 'on_trip' lain
        $userOverlap = Booking::whereIn('status', ['approved', 'on_trip'])
            ->where(function ($query) use ($validated) {
                $query->where('start_date', '<', $validated['end_date'])
                    ->where('end_date', '>', $validated['start_date']);
            })
            ->where(function ($query) use ($allPersonnelIds) {
                // Cek jika ID ada sebagai Peminjam Utama
                $query->whereIn('user_id', $allPersonnelIds);

                // Atau jika ID tersimpan sebagai anggota tim dalam JSON/Array 'participants'
                foreach ($allPersonnelIds as $personnelId) {
                    $query->orWhereJsonContains('participants', (string) $personnelId)
                        ->orWhereJsonContains('participants', (int) $personnelId);
                }
            })
            ->first();

        if ($userOverlap) {
            return back()
                ->withInput($request->except('letter_file') + ['letter_file_temp' => $validated['letter_file'] ?? null])
                ->withErrors([
                    'participants' => 'Anda atau salah satu anggota tim yang dipilih sudah memiliki jadwal perjalanan dinas lain yang disetujui pada rentang waktu ini.'
                ]);
        }

        // 3. Simpan Data Booking
        $validated['letter_slug'] = $this->generateUniqueSlug($validated['letter_number']);
        $validated['user_id'] = Auth::id();
        $validated['status']  = 'pending';

        $booking = Booking::create($validated);

        // 🚀 Notifikasi WA Pengajuan Baru
        // A. Kirim ke Admin
        $adminPhones = User::where('role', 'admin')->pluck('phone')->filter()->toArray();
        $msgAdmin = "📩 *PERMOHONAN PEMINJAMAN BARU*\n\n"
                . "No. Surat: {$booking->letter_number}\n"
                . "Pemohon: " . Auth::user()->name . "\n"
                . "Tujuan: {$booking->destination}\n"
                . "Jadwal: {$booking->start_date->format('d/m/Y H:i')} s.d. {$booking->end_date->format('d/m/Y H:i')}\n\n"
                . "Mohon periksa dan proses persetujuan melalui dashboard.";

        WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);

        // B. Kirim ke Pemohon
        $msgApplicant = "✅ *PENGAJUAN PEMINJAMAN BERHASIL*\n\n"
                . "No. Surat: {$booking->letter_number}\n"
                . "Tujuan: {$booking->destination}\n"
                . "Jadwal: {$booking->start_date->format('d/m/Y H:i')} s.d. {$booking->end_date->format('d/m/Y H:i')}\n\n"
                . "Permohonan Anda sedang menunggu persetujuan.";

        WhatsAppService::sendMessage(Auth::user()->phone, $msgApplicant);

        // C. Kirim ke Anggota Tim (jika ada)
        if (!empty($booking->participants)) {
            // Asumsi participants menyimpan ID user / nomor HP
            $participantPhones = User::whereIn('id', $booking->participants)->pluck('phone')->filter()->toArray();
            $msgMember = "ℹ️ *INFORMASI PERJALANAN DINAS*\n\n"
                    . "Anda telah didaftarkan oleh *" . Auth::user()->name . "* sebagai anggota tim peminjaman armada ke *{$booking->destination}* pada tanggal *{$booking->start_date->format('d/m/Y')}*.";

            WhatsAppService::sendBulkMessage($participantPhones, $msgMember);
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Pengajuan peminjaman berhasil dikirim dengan Surat Perintah.');
    }

    public function show(Booking $booking)
    {
        $booking->load(['user', 'vehicle', 'approver', 'log']);
        return view('bookings.show', compact('booking'));
    }

    public function edit(Booking $booking)
    {
        // Hanya izinkan status 'pending' atau 'approved'
        if (!in_array($booking->status, ['pending', 'approved'])) {
            return redirect()->route('bookings.index')
                ->withErrors(['error' => 'Permohonan yang sudah berjalan atau selesai tidak dapat diubah.']);
        }

        // Peminjam hanya bisa edit jika pending, Admin bisa edit pending maupun approved
        if ($booking->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Ambil seluruh kendaraan beserta relasi booking aktifnya (Kecuali booking saat ini)
        $vehicles = Vehicle::with(['bookings' => function ($q) use ($booking) {
            $q->where('id', '!=', $booking->id) // Abaikan booking ini sendiri
            ->whereIn('status', ['approved', 'on_trip'])
            ->where('end_date', '>=', now())
            ->orderBy('start_date', 'asc');
        }])->get();
        
        // Ambil daftar seluruh pegawai
        $employees = User::where('role', 'pegawai')
            ->where('id', '!=', $booking->user_id)
            ->orderBy('name', 'asc')
            ->get();

        return view('bookings.edit', compact('booking', 'vehicles', 'employees'));
    }

    public function update(Request $request, Booking $booking)
    {
        // 1. Izinkan status 'pending' ATAU 'approved'
        if (!in_array($booking->status, ['pending', 'approved'])) {
            return redirect()->route('bookings.index')
                ->withErrors(['error' => 'Permohonan ini tidak dapat diubah lagi.']);
        }

        // 2. Otorisasi Akses: Peminjam hanya bisa update jika status pending, Admin bisa update pending & approved
        if ($booking->user_id !== Auth::id() && Auth::user()->role !== 'admin' ) {
            abort(403);
        }

        $validated = $request->validate([
            'letter_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bookings', 'letter_number')->where(function ($query) use ($booking) {
                    return $query->whereNotIn('status', ['canceled', 'rejected'])->where('id', '!=', $booking->id);
                }),
            ],
            'vehicle_id'    => ['required', 'exists:vehicles,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after:start_date'],
            'destination'   => ['required', 'string', 'max:255'],
            'purpose'       => ['required', 'string'],
            'letter_file'   => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'participants'  => ['nullable', 'array'],
        ]);

        // 1. Filter Array Anggota Tim (dibersihkan dari nilai null/kosong)
        if (isset($validated['participants'])) {
            $validated['participants'] = array_values(array_filter($validated['participants']));
        }

        // ------------------------------------------------------------------
        // A. CEK BENTROK JADWAL KENDARAAN (Kecuali Peminjaman Ini Sendiri)
        // ------------------------------------------------------------------
        $isVehicleOverlapped = Booking::where('vehicle_id', $validated['vehicle_id'])
            ->where('id', '!=', $booking->id)
            ->whereIn('status', ['approved', 'on_trip'])
            ->where(function ($query) use ($validated) {
                $query->where('start_date', '<', $validated['end_date'])
                    ->where('end_date', '>', $validated['start_date']);
            })
            ->exists();

        if ($isVehicleOverlapped) {
            return back()->withInput()->withErrors([
                'vehicle_id' => 'Mobil ini sudah memiliki jadwal peminjaman yang disetujui pada jam/tanggal tersebut.'
            ]);
        }

        // ------------------------------------------------------------------
        // B. CEK BENTROK JADWAL PEMINJAM UTAMA & ANGGOTA TIM
        // ------------------------------------------------------------------
        // Kumpulkan seluruh ID personil yang terlibat (Peminjam Utama + Anggota Tim)
        $allPersonnelIds = array_merge([$booking->user_id], $validated['participants'] ?? []);

        $userOverlap = Booking::where('id', '!=', $booking->id)
            ->whereIn('status', ['approved', 'on_trip'])
            ->where(function ($query) use ($validated) {
                $query->where('start_date', '<', $validated['end_date'])
                    ->where('end_date', '>', $validated['start_date']);
            })
            ->where(function ($query) use ($allPersonnelIds) {
                // Cek jika ID ada sebagai Peminjam Utama
                $query->whereIn('user_id', $allPersonnelIds);

                // Atau jika ID tersimpan sebagai anggota tim dalam JSON/Array 'participants'
                foreach ($allPersonnelIds as $personnelId) {
                    $query->orWhereJsonContains('participants', (string) $personnelId)
                        ->orWhereJsonContains('participants', (int) $personnelId);
                }
            })
            ->first();

        if ($userOverlap) {
            return back()->withInput()->withErrors([
                'participants' => 'Pemohon atau salah satu anggota tim yang dipilih sudah memiliki jadwal perjalanan dinas lain yang disetujui pada rentang waktu ini.'
            ]);
        }

        // 2. Penanganan Upload File Surat Perintah
        if ($request->hasFile('letter_file')) {
            $validated['letter_file'] = $request->file('letter_file')->store('letters', 'public');
        }

        if ($booking->letter_number !== $validated['letter_number']) {
            $validated['letter_slug'] = $this->generateUniqueSlug($validated['letter_number'], $booking->id);
        }

        // 3. Simpan Perubahan
        $booking->update($validated);

        return redirect()->route('bookings.index')->with('success', 'Permohonan peminjaman berhasil diperbarui.');
    }

    public function approve(Request $request, Booking $booking)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'status'     => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string',
        ]);

        if ($request->status === 'approved') {
            // 1. CEK BENTROKAN KENDARAAN
            $vehicleOverlap = Booking::where('vehicle_id', $booking->vehicle_id)
                ->where('id', '!=', $booking->id)
                ->whereIn('status', ['approved', 'on_trip'])
                ->where(function ($query) use ($booking) {
                    $query->where('start_date', '<', $booking->end_date)
                        ->where('end_date', '>', $booking->start_date);
                })
                ->exists();

            if ($vehicleOverlap) {
                return back()->with('error', 'Gagal menyetujui! Mobil ini sudah disetujui untuk peminjaman lain pada jadwal yang sama.');
            }

            // 2. CEK BENTROKAN PEMINJAM (USER UTAMA)
            $userOverlap = Booking::where('user_id', $booking->user_id)
                ->where('id', '!=', $booking->id)
                ->whereIn('status', ['approved', 'on_trip'])
                ->where(function ($query) use ($booking) {
                    $query->where('start_date', '<', $booking->end_date)
                        ->where('end_date', '>', $booking->start_date);
                })
                ->exists();

            if ($userOverlap) {
                return back()->with('error', 'Gagal menyetujui! Pegawai peminjam sudah memiliki agenda perjalanan dinas lain yang disetujui pada jadwal tersebut.');
            }
        }

        $booking->update([
            'status'      => $request->status,
            'admin_note'  => $request->admin_note,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $message = $request->status === 'approved' 
            ? 'Pengajuan peminjaman berhasil disetujui.' 
            : 'Pengajuan peminjaman telah ditolak.';

        // Kirim notifikasi WA ke pemohon
        if ($request->status === 'approved') {
            $pemohonPhone = $booking->user->phone;
            $msgApprove = "✅ *PERMOHONAN DISETUJUI*\n\n"
                . "Peminjaman kendaraan ke *{$booking->destination}* telah *DISETUJUI* oleh Admin.\n"
                . "Armada: " . ($booking->vehicle->model ?? '-') . " (" . ($booking->vehicle->plate_number ?? '-') . ")\n"
                . "Jadwal: {$booking->start_date->format('d/m/Y H:i')} s.d. {$booking->end_date->format('d/m/Y H:i')}\n\n"
                . "Silakan lakukan Check-Out saat keberangkatan.";

            WhatsAppService::sendMessage($pemohonPhone, $msgApprove);
        } else {
            $pemohonPhone = $booking->user->phone;
            $msgReject = "❌ *PERMOHONAN DITOLAK*\n\n"
                . "Pengajuan peminjaman kendaraan ke *{$booking->destination}* telah *DITOLAK* oleh Admin.\n"
                . "Alasan: " . ($request->admin_note ?? 'Tidak ada catatan tambahan.') . "\n\n"
                . "Silakan hubungi Admin untuk informasi lebih lanjut.";

            WhatsAppService::sendMessage($pemohonPhone, $msgReject);
        }

        return back()->with('success', $message);
    }

    // 1. Tampilan Halaman Kalender
    public function calendar()
    {
        return view('bookings.calendar');
    }

    // 2. Endpoint API JSON untuk FullCalendar
    public function getEvents(Request $request)
    {
        $bookings = Booking::with(['vehicle', 'user'])
            ->whereIn('status', ['approved', 'on_trip', 'unconfirmed', 'completed'])
            ->get();

        $events = $bookings->map(function ($booking) {
            // Warna status jadwal di kalender
            $color = match ($booking->status) {
                'approved'  => '#3B82F6', // Blue
                'on_trip'   => '#A855F7', // Purple
                'unconfirmed' => '#EAB308', // Yellow
                'completed' => '#10B981', // Green
                default     => '#6B7280',
            };

            return [
                'id'    => $booking->id,
                'title' => $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ') - ' . $booking->user->name,
                'start' => $booking->start_date->toIso8601String(),
                'end'   => $booking->end_date->toIso8601String(),
                'color' => $color,
                'extendedProps' => [
                    'destination' => $booking->destination,
                    'purpose'     => $booking->purpose,
                    'status'      => strtoupper($booking->status),
                ]
            ];
        });

        return response()->json($events);
    }

    public function getCalendarEvents(Request $request)
    {
        $bookings = Booking::with(['user', 'vehicle'])
            ->whereIn('status', ['approved', 'on_trip', 'unconfirmed', 'completed'])
            ->get();

        $events = $bookings->map(function ($booking) {
            return [
                'id'    => $booking->id,
                'title' => ($booking->vehicle->name ?? 'Mobil') . ' - ' . ($booking->user->name ?? 'User'),
                'start' => $booking->start_date->toIso8601String(),
                'end'   => $booking->end_date->toIso8601String(),
                'color' => match($booking->status) {
                    'approved'    => '#3b82f6', // blue-500
                    'on_trip'     => '#a855f7', // purple-500
                    'unconfirmed' => '#eab308', // yellow-500
                    'completed'   => '#22c55e', // green-500
                    default       => '#6b7280',
                },
                'extendedProps' => [
                    'destination' => $booking->destination,
                    'purpose'     => $booking->purpose,
                    'status'      => $booking->status,
                    'letter_slug' => $booking->letter_slug, // 👈 Sertakan ini untuk tombol link detail
                ]
            ];
        });

        return response()->json($events);
    }

    public function checkOut(Request $request, Booking $booking)
    {
        // Cari KM terakhir dari kendaraan ini
        $lastLog = VehicleLog::where('vehicle_id', $booking->vehicle_id)
            ->latest()
            ->first();
        $minKm = $lastLog ? ($lastLog->end_km ?? $lastLog->start_km) : 0;

        $validated = $request->validate([
            'start_km'         => "required|numeric|gte:{$minKm}",
            'start_fuel_level' => 'required|string|max:20',
            'start_photo'      => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'start_km.required'    => 'Angka Odometer / KM saat ini wajib diisi.',
            'start_km.gte'         => "Nilai KM start tidak boleh kurang dari KM terakhir armada (" . number_format($minKm) . " KM).",
            'start_photo.required' => 'Foto kondisi Odometer dan Sisa BBM saat Check-Out wajib diunggah.',
            'start_photo.image'    => 'Berkas yang diunggah harus berupa gambar.',
            'start_photo.max'      => 'Ukuran foto tidak boleh melebihi 5 MB.',
        ]);

        $path = $request->file('start_photo')->store('vehicle_logs', 'public');

        // Gunakan Transaction agar aman jika salah satu proses gagal
        DB::transaction(function () use ($booking, $validated, $path) {
            VehicleLog::create([
                'booking_id'       => $booking->id,
                'vehicle_id'       => $booking->vehicle_id, // Terisi dengan benar
                'start_km'         => $validated['start_km'],
                'start_fuel_level' => $validated['start_fuel_level'],
                'start_photo'      => $path,
                'checked_out_at'   => now(),
            ]);

            $booking->update(['status' => 'on_trip']);
            $booking->vehicle->update(['status' => 'borrowed']);
        });

        // 🚀 Notifikasi WA Keberangkatan
        $msgDeparture = "🚀 *KEBERANGKATAN ARMADA*\n\n"
                    . "Kendaraan *" . ($booking->vehicle->model ?? '-') . " (" . ($booking->vehicle->plate_number ?? '-') . ")* resmi dilaporkan *BERANGKAT* ke *{$booking->destination}*.\n\n"
                    . "Odometer Awal: {$booking->log->start_km} KM\n"
                    . "Sisa BBM: {$booking->log->start_fuel_level}\n"
                    . "Jadwal: {$booking->start_date->format('d/m/Y H:i')} s.d. {$booking->end_date->format('d/m/Y H:i')}\n\n"
                    . "Hati-hati di jalan dan selamat sampai tujuan!";

        // Kirim ke Pemohon
        WhatsAppService::sendMessage($booking->user->phone, $msgDeparture);

        // Kirim ke Admin
        $adminPhones = User::where('role', 'admin')->pluck('phone')->filter()->toArray();
        WhatsAppService::sendBulkMessage($adminPhones, $msgDeparture);

        return redirect()->route('bookings.index')->with('success', 'Check-out berhasil. Selamat jalan!');
    }

    public function updateCheckOut(Request $request, Booking $booking)
    {
        // Hanya izinkan jika status sedang 'on_trip' atau 'unconfirmed'
        if (!in_array($booking->status, ['on_trip', 'unconfirmed'])) {
            return back()->withErrors(['error' => 'Data keberangkatan tidak dapat diubah pada status ini.']);
        }

        // Akses hanya untuk Admin atau Peminjam Asli
        if ($booking->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'start_km'         => 'required|numeric|min:0',
            'start_fuel_level' => 'required|string',
            'start_photo'      => 'nullable|image|max:5120',
        ]);

        $log = $booking->log;

        if (!$log) {
            return back()->withErrors(['error' => 'Log keberangkatan tidak ditemukan.']);
        }

        // Update foto jika ada foto baru yang diunggah
        if ($request->hasFile('start_photo')) {
            if ($log->start_photo && Storage::disk('public')->exists($log->start_photo)) {
                Storage::disk('public')->delete($log->start_photo);
            }
            $validated['start_photo'] = $request->file('start_photo')->store('vehicle_logs', 'public');
        }

        // Hitung ulang jarak tempuh jika KM akhir sudah pernah diisi
        if ($log->end_km) {
            $validated['distance_traveled'] = max(0, $log->end_km - $validated['start_km']);
        }

        $log->update($validated);

        return redirect()->route('bookings.index')
            ->with('success', 'Data keberangkatan (Check-Out) berhasil diperbaiki.');
    }

    public function checkIn(Request $request, Booking $booking)
    {
        $startKm = $booking->log->start_km ?? 0;

        $validated = $request->validate([
            'end_km'             => ['required', 'numeric', 'gt:' . $startKm],
            'end_fuel_level'     => 'required|string|max:20',
            'condition_notes'    => 'nullable|string',
            'condition_photos'   => 'nullable|array',
            'condition_photos.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'end_photo'          => 'required|image|mimes:jpg,jpeg,png|max:5120', // WAJIB UPLOAD FOTO (MAX 5MB)
        ], [
            'end_km.gt'          => 'Odometer pengembalian (KM Akhir) harus lebih besar dari KM Awal saat Check-Out (' . number_format($startKm) . ' KM).',
            'end_photo.required' => 'Foto kondisi Odometer dan Sisa BBM saat Check-In wajib diunggah.',
            'end_photo.image'    => 'Berkas yang diunggah harus berupa gambar.',
            'end_photo.max'      => 'Ukuran foto tidak boleh melebihi 5 MB.',
        ]);

        $log = $booking->log;
        $distance = $validated['end_km'] - $log->start_km;
        
        // 1. Simpan Foto Odometer Check-In
        $path = $request->file('end_photo')->store('vehicle_logs', 'public');

        // 2. Simpan Multiple Foto Kondisi / Kerusakan Mobil (jika ada)
        $conditionPhotoPaths = [];
        if ($request->hasFile('condition_photos')) {
            foreach ($request->file('condition_photos') as $photo) {
                $conditionPhotoPaths[] = $photo->store('vehicle_logs', 'public');
            }
        }

        // 3. Update Log Kendaraan
        $log->update([
            'end_km'            => $validated['end_km'],
            'end_fuel_level'    => $validated['end_fuel_level'],
            'distance_traveled' => $distance,
            'condition_notes'   => $validated['condition_notes'] ?? null,
            'condition_photos'  => !empty($conditionPhotoPaths) ? $conditionPhotoPaths : null,
            'end_photo'         => $path,
            'checked_in_at'     => now(),
        ]);

        // 4. Update Status Booking dan Kendaraan
        $booking->update(['status' => 'unconfirmed']);

        // 🚀 Notifikasi WA Pengembalian
        $msgReturn = "🏁 *PENGEMBALIAN ARMADA*\n\n"
                . "Kendaraan *" . ($booking->vehicle->model ?? '-') . " (" . ($booking->vehicle->plate_number ?? '-') . ")* telah dikembalikan oleh *" . $booking->user->name . "* pada tanggal *" . now()->format('d/m/Y H:i') . "*.\n"
                . "Odometer Akhir: {$booking->log->end_km} KM\n"
                . "Sisa BBM: {$booking->log->end_fuel_level}\n\n"
                . "Catatan Kondisi: " . ($booking->log->condition_notes ?? 'Tidak ada catatan tambahan.') . "\n\n"
                . "Status: Menunggu konfirmasi verifikasi admin.";

        WhatsAppService::sendMessage($booking->user->phone, $msgReturn);

        $adminPhones = User::where('role', 'admin')->pluck('phone')->filter()->toArray();
        WhatsAppService::sendBulkMessage($adminPhones, $msgReturn);

        return redirect()->route('bookings.index')
            ->with('success', 'Check-in berhasil. Total jarak tempuh: ' . number_format($distance) . ' KM.');
    }

    public function updateCheckIn(Request $request, Booking $booking)
    {
        // Peminjam hanya bisa edit jika status 'unconfirmed', Admin bisa edit 'unconfirmed' dan 'completed'
        if (!in_array($booking->status, ['unconfirmed', 'completed'])) {
            return back()->withErrors(['error' => 'Data pengembalian tidak dapat diubah pada status ini.']);
        }

        if (Auth::user()->role !== 'admin' && $booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Peminjam tidak boleh mengedit jika sudah berstatus 'completed' (hanya Admin)
        if ($booking->status === 'completed' && Auth::user()->role !== 'admin') {
            return back()->withErrors(['error' => 'Data pengembalian yang sudah diverifikasi Admin tidak dapat diubah kembali.']);
        }

        $log = $booking->log;
        if (!$log) {
            return back()->withErrors(['error' => 'Log pengembalian tidak ditemukan.']);
        }

        $validated = $request->validate([
            'end_km'            => 'required|numeric|min:' . ($log->start_km + 1),
            'end_fuel_level'    => 'required|string',
            'end_photo'         => 'nullable|image|max:5120',
            'condition_notes'   => 'nullable|string',
            'condition_photos.*'=> 'nullable|image|max:5120',
        ]);

        // 1. Update Foto Odometer Akhir jika diunggah file baru
        if ($request->hasFile('end_photo')) {
            if ($log->end_photo && Storage::disk('public')->exists($log->end_photo)) {
                Storage::disk('public')->delete($log->end_photo);
            }
            $validated['end_photo'] = $request->file('end_photo')->store('vehicle_logs', 'public');
        }

        // 2. Append / Tambah Foto Bukti Kerusakan Baru (jika ada)
        if ($request->hasFile('condition_photos')) {
            $existingPhotos = $log->condition_photos ?? [];
            $newPhotos = [];
            foreach ($request->file('condition_photos') as $photo) {
                $newPhotos[] = $photo->store('vehicle_conditions', 'public');
            }
            $validated['condition_photos'] = array_merge($existingPhotos, $newPhotos);
        }

        // 3. Hitung Ulang Jarak Tempuh
        $validated['distance_traveled'] = max(0, $validated['end_km'] - $log->start_km);

        // 4. Update Log Pengembalian
        $log->update($validated);

        // 5. Jika status sudah 'completed', perbarui juga KM Terakhir di master tabel Kendaraan
        if ($booking->status === 'completed') {
            $booking->vehicle->update([
                'last_km' => $validated['end_km']
            ]);
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Data pengembalian (Check-In) berhasil diperbarui.');
    }

    public function confirmDeparture(Request $request, Booking $booking)
    {
        if (!in_array($booking->status, ['on_trip', 'unconfirmed'])) {
            return back()->withErrors(['error' => 'Keberangkatan hanya dapat dikonfirmasi setelah data keberangkatan diisi.']);
        }

        if (Auth::user()->role !== 'admin' && $booking->user_id !== Auth::id()) {
            abort(403, 'Anda tidak berwenang mengonfirmasi keberangkatan ini.');
        }

        // 1. Catat data konfirmasi keberangkatan & ubah status ke on_trip
        $booking->update([
            'departed_confirmed_by' => Auth::id(),
            'departed_at'           => now(),
        ]);

        // 2. Cek apakah pengembalian ternyata sudah terisi duluan (Safety Check)
        $this->checkAndSetCompletedStatus($booking);

        return redirect()->route('bookings.index')
            ->with('success', 'Keberangkatan berhasil dikonfirmasi.');
    }

    public function confirmReturn(Request $request, Booking $booking)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($booking->status !== 'unconfirmed') {
            return back()->withErrors(['error' => 'Permohonan ini belum dalam status menunggu konfirmasi pengembalian.']);
        }

        // 1. Update Booking ke Completed & simpan penanggung jawab pengembalian
        $booking->update([
            'returned_confirmed_by' => Auth::id(),
            'returned_at' => now(),
        ]);

        // 2. Cek apakah kedua konfirmasi (Departure & Return) sudah lengkap
        $this->checkAndSetCompletedStatus($booking);

        return redirect()->route('bookings.index')
            ->with('success', 'Pengembalian kendaraan berhasil dikonfirmasi.');
    }

    private function checkAndSetCompletedStatus(Booking $booking)
    {
        // Refresh data booking dari database
        $booking->refresh();

        // Syarat Otomatis Complete: departed_confirmed_by DAN returned_confirmed_by harus sama-sama TIDAK NULL
        if (!is_null($booking->departed_confirmed_by) && !is_null($booking->returned_confirmed_by)) {
            
            // 1. Ubah status booking ke completed
            $booking->update([
                'status' => 'completed',
            ]);

            // 2. Bebaskan kendaraan menjadi available
            if ($booking->vehicle) {
                $booking->vehicle->update(['status' => 'available']);
            }

            // 🚀 Notifikasi WA Completed
            $msgCompleted = "🎉 *PEMINJAMAN SELESAI & TERVERIFIKASI*\n\n"
                        . "Peminjaman kendaraan ke *{$booking->destination}* telah resmi *DIVERIFIKASI & SELESAI*.\n"
                        . "Kendaraan *" . ($booking->vehicle->model ?? '-') . " (" . ($booking->vehicle->plate_number ?? '-') . ")* kini kembali berstatus *TERSEDIA*.\n\n"
                        . "Terima kasih atas kerja samanya!";

            WhatsAppService::sendMessage($booking->user->phone, $msgCompleted);
        }
    }

    public function cancel(Booking $booking)
    {
        /** @var User|null $actor */
        $actor = Auth::user();

        // 1. Otorisasi: Hanya pembuat peminjaman atau Admin yang boleh membatalkan
        if ($booking->user_id !== Auth::id() && ($actor === null || $actor->role !== 'admin')) {
            return back()->withErrors(['error' => 'Anda tidak memiliki hak akses untuk membatalkan permohonan ini.']);
        }

        // 2. Batasan Status: Hanya permohonan berstatus 'pending' atau 'approved' yang bisa dibatalkan
        if (!in_array($booking->status, ['pending', 'approved'])) {
            return back()->withErrors(['error' => 'Permohonan yang sedang berjalan (on_trip) atau sudah selesai tidak dapat dibatalkan.']);
        }

        // 3. Ubah status peminjaman menjadi 'canceled'
        $booking->update([
            'status' => 'canceled',
        ]);

        // 4. Jika mobil sempat disetujui/dikunci, pastikan status ketersediaan mobil dipulihkan ke 'available'
        if ($booking->vehicle && $booking->vehicle->status === 'borrowed') {
            $booking->vehicle->update(['status' => 'available']);
        }

        // 🚀 Notifikasi WA Pembatalan (Cancel)
        $cancellerName = $actor?->name ?? 'Admin';

        $msgCancel = "🚫 *PEMBATALAN PEMINJAMAN ARMADA*\n\n"
                . "Permohonan peminjaman kendaraan ke *{$booking->destination}* (No. Surat: {$booking->letter_number}) telah *DIBATALKAN* oleh *{$cancellerName}*.\n\n"
                . "Armada *" . ($booking->vehicle->name ?? '-') . "* kini telah dibebaskan kembali.";

        // 1. Kirim Notifikasi ke Pemohon
        if ($booking->user && $booking->user->phone) {
            WhatsAppService::sendMessage($booking->user->phone, $msgCancel);
        }

        // 2. Kirim Notifikasi ke Admin (Jika yang membatalkan adalah Pegawai/Pemohon)
        if ($actor && $actor->role !== 'admin') {
            $adminPhones = User::where('role', 'admin')->pluck('phone')->filter()->toArray();
            WhatsAppService::sendBulkMessage($adminPhones, $msgCancel);
        }

        // 3. Kirim Notifikasi ke Anggota Tim (Jika ada)
        if (!empty($booking->participants)) {
            $participantPhones = User::whereIn('id', $booking->participants)->pluck('phone')->filter()->toArray();
            WhatsAppService::sendBulkMessage($participantPhones, $msgCancel);
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Permohonan peminjaman berhasil dibatalkan. Data tetap tersimpan dalam riwayat.');
    }

    private function generateUniqueSlug(string $letterNumber, ?int $ignoreBookingId = null): string
    {
        // Hanya ganti '/' dengan '-' agar konsisten dengan data lama di DB (titik '.' akan otomatis dihilangkan oleh Str::slug)
        $formattedNumber = str_replace('/', '-', $letterNumber);
        $baseSlug = Str::slug($formattedNumber);
        
        $slug = $baseSlug;
        $count = 1;

        while (
            Booking::where('letter_slug', $slug)
                ->when($ignoreBookingId, function ($query) use ($ignoreBookingId) {
                    return $query->where('id', '!=', $ignoreBookingId);
                })
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    public function printDeparture(Booking $booking)
    {
        if (!in_array($booking->status, ['on_trip', 'unconfirmed', 'completed'])) {
            abort(403, 'Peminjaman belum berangkat.');
        }

        $booking->load(['user', 'vehicle', 'log', 'approver']);

        // Teks Payload QR Code untuk Admin / Approver
        $departureNip = $booking->departureConfirmer->nip ?? '-';
        $departureName = $booking->departureConfirmer->name ?? 'Admin Operasional';
        $departureTime = $booking->departed_at ? $booking->departed_at->format('d/m/Y H:i') : '-';
        
        $qrTextAdmin = "DITANDATANGANI SECARA DIGITAL\n" .
                    "Oleh: {$departureName}\n" .
                    "NIP: {$departureNip}\n" .
                    "No Surat: {$booking->letter_number}\n" .
                    "Diverifikasi Pada: ({$departureTime})";

        // Teks Payload QR Code untuk Peminjam Utama
        $userNip = $booking->user->nip ?? '-';
        $qrTextUser = "DITANDATANGANI SECARA DIGITAL\n" .
                    "Oleh: {$booking->user->name}\n" .
                    "NIP: {$userNip}\n" .
                    "No Surat: {$booking->letter_number}";

        // Generate SVG QR Code (Inline, hemat storage)
        $qrAdmin = QrCode::size(90)->margin(1)->generate($qrTextAdmin);
        $qrUser  = QrCode::size(90)->margin(1)->generate($qrTextUser);

        return view('bookings.print_departure', compact('booking', 'qrAdmin', 'qrUser'));
    }

    public function printReturn(Booking $booking)
    {
        if (!in_array($booking->status, ['on_trip', 'unconfirmed', 'completed'])) {
            abort(403, 'Peminjaman belum berangkat.');
        }

        // Pastikan relasi ke model User menggunakan foreign key returned_by (misal relasi 'returnApprover' atau 'returnedBy')
        $booking->load(['user', 'vehicle', 'log', 'approver', 'returnApprover']);

        // Ambil data petugas penerima dari relasi returned_by
        $returnUser = $booking->returnApprover ?? $booking->returnedBy;
        $adminName  = $returnUser->name ?? 'Admin Operasional';
        $adminNip   = $returnUser->nip ?? '-';

        // Format tanggal pengembalian dari atribut returned_at
        $returnTime = $booking->returned_at 
            ? \Carbon\Carbon::parse($booking->returned_at)->format('d/m/Y H:i') 
            : '-';

        $qrTextUser = "DITANDATANGANI SECARA DIGITAL\n" .
                    "Oleh: {$booking->user->name}\n" .
                    "NIP: " . ($booking->user->nip ?? '-') . "\n" .
                    "No Surat: {$booking->letter_number}";

        $qrTextAdmin = "DITANDATANGANI SECARA DIGITAL\n" .
                    "Oleh: {$adminName}\n" .
                    "NIP: {$adminNip}\n" .
                    "No Surat: {$booking->letter_number}\n" .
                    "Diverifikasi Pada: {$returnTime}";

        $qrUser  = QrCode::size(90)->margin(1)->generate($qrTextUser);
        $qrAdmin = QrCode::size(90)->margin(1)->generate($qrTextAdmin);

        return view('bookings.print_return', compact('booking', 'qrAdmin', 'qrUser'));
    }
}