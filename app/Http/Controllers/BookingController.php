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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Smalot\PdfParser\Parser;

class BookingController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // Query Data Peminjaman
        $bookings = Booking::with(['user', 'vehicle', 'driver', 'log'])
            ->when(!in_array($user->role, ['admin_app', 'admin_lap', 'approver']), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                    ->orWhere('driver_id', $user->id)
                    ->orWhereJsonContains('participants', (string) $user->id)
                    ->orWhereJsonContains('participants', (int) $user->id);
                });
            })
            ->latest()
            ->paginate(10);

        // Ambil data pendukung untuk Modal Peninjauan (Admin Lapangan)
        $availableVehicles = Vehicle::where('status', '!=', 'maintenance')->where('type', 'operational')->orderBy('code', 'asc')->get();
        $availableDrivers  = User::where('role', 'driver')->get();

        $activeBookings = Booking::whereIn('status', ['approved', 'on_trip'])
        ->whereNotNull('vehicle_id')
        ->select('id', 'vehicle_id', 'driver_id', 'start_date', 'end_date')
        ->get()
        ->map(function ($b) {
            return [
                'id'         => $b->id,
                'vehicle_id' => $b->vehicle_id,
                'driver_id'  => $b->driver_id,
                'start_date' => $b->start_date->format('Y-m-d H:i:s'),
                'end_date'   => $b->end_date->format('Y-m-d H:i:s'),
            ];
        });

        // Kalkulasi Odometer & Flag Reassign
        $vehicleIds = $bookings->getCollection()->pluck('vehicle_id')->filter()->unique();

        $latestLogs = VehicleLog::whereIn('vehicle_id', $vehicleIds)
            ->select('vehicle_id', 'start_km', 'end_km')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('vehicle_logs')
                    ->groupBy('vehicle_id');
            })
            ->get()
            ->keyBy('vehicle_id');

        $today = now()->startOfDay();

        $bookings->getCollection()->transform(function ($booking) use ($latestLogs, $today) {
            if ($booking->vehicle) {
                $lastLog = $latestLogs->get($booking->vehicle_id);

                $booking->vehicle->last_km = $lastLog 
                    ? ($lastLog->end_km ?? $lastLog->start_km ?? 0) 
                    : 0;

                $booking->vehicle->last_log_end_km = $lastLog ? $lastLog->end_km : null;
            }

            $booking->needs_reassign = ($booking->status === 'approved')
                && ($booking->vehicle?->status === 'maintenance')
                && ($booking->start_date >= $today);

            return $booking;
        });

        return view('bookings.index', compact('bookings', 'availableVehicles', 'availableDrivers', 'activeBookings'));
    }

    public function create()
    {
        // 1. Ambil pengguna dengan role: admin_lap, pegawai, driver
        $users = User::whereIn('role', ['admin_lap', 'pegawai', 'driver'])
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'nip', 'jabatan', 'role']);

        // 2. Ambil armada monitoring
        $monitoringVehicles = Vehicle::where('type', 'monitoring')
            ->where('status', '!=', 'maintenance')
            ->orderBy('code', 'asc')
            ->get();

        // 3. Ambil armada operasional
        $operationalVehicles = Vehicle::where('type', 'operational')
            ->where('status', '!=', 'maintenance')
            ->orderBy('code', 'asc')
            ->get();

        return view('bookings.create', compact('users', 'monitoringVehicles', 'operationalVehicles'));
    }

    public function parseDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:5120',
        ]);

        try {
            $file = $request->file('document');
            $filePath = $file->store('letters', 'public');

            // 1. Baca isi berkas PDF dan konversi ke Base64
            $pdfBase64 = base64_encode(file_get_contents($file->getPathname()));

            // 2. Prompt ekstraksi data untuk AI
            $prompt = "Kamu adalah sistem ekstraktor dokumen resmi. Analisis dokumen Surat Tugas dalam berkas PDF ini dan ekstrak datanya ke dalam format JSON murni.

    Data yang harus diekstrak:
    1. letter_number: Nomor surat tugas lengkap.
    2. destination: Tempat/Lokasi pelaksanaan (misal: 'Kota Tegal' atau 'Kabupaten Temanggung').
    3. purpose: Maksud/keperluan kegiatan.
    4. start_date: Tanggal mulai pelaksanaan (format YYYY-MM-DD).
    5. end_date: Tanggal selesai pelaksanaan (format YYYY-MM-DD).
    6. nips: Array berisi daftar NIP (18 digit angka) dari pegawai yang ditugaskan. Jika ada titik/spasi pada NIP, bersihkan sehingga hanya angka murni.";

            // 3. Ambil konfigurasi API Key & Model dari config
            $apiKey = config('services.gemini.api_key');
            $model  = config('services.gemini.model', 'gemini-flash-latest');

            // 4. Panggil Gemini API Multimodal
            $response = Http::timeout(60)
                ->retry(2, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => 'application/pdf',
                                        'data'      => $pdfBase64
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ]
                ]);

            if ($response->failed()) {
                throw new \Exception('Gagal terhubung ke AI Service: ' . $response->body());
            }

            $responseData = $response->json();
            $aiRawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $aiResult = json_decode($aiRawText, true) ?? [];

            // 5. Cocokkan NIP hasil temuan AI dengan Database User
            $matchedUserIds = [];
            if (!empty($aiResult['nips']) && is_array($aiResult['nips'])) {
                // Bersihkan format NIP (hanya angka)
                $cleanedNips = array_map(fn($nip) => preg_replace('/[^0-9]/', '', $nip), $aiResult['nips']);

                $matchedUserIds = User::whereIn('nip', $cleanedNips)
                    ->pluck('id')
                    ->map(fn($id) => (string)$id)
                    ->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'letter_number' => $aiResult['letter_number'] ?? '',
                    'letter_file'   => $filePath,
                    'destination'   => $aiResult['destination'] ?? '',
                    'purpose'       => $aiResult['purpose'] ?? '',
                    'start_date'    => $aiResult['start_date'] ?? null,
                    'end_date'      => $aiResult['end_date'] ?? null,
                    'user_ids'      => array_values($matchedUserIds),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca Surat Tugas via AI: ' . $e->getMessage()
            ], 422);
        }
    }

    private function generateUniqueSlug(string $letterNumber, ?int $ignoreBookingId = null): string
    {
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

    public function store(StoreBookingRequest $request)
    {
        // dd($request->all());
        $validated = $request->validated();

        // 1. Penanganan Upload File Surat Perintah (PDF)
        if ($request->hasFile('letter_file')) {
            $validated['letter_file'] = $request->file('letter_file')->store('letters', 'public');
        } elseif ($request->filled('letter_file_temp')) {
            $validated['letter_file'] = $request->input('letter_file_temp');
        }

        // 2. Filter & Bersihkan Array Anggota Tim (Array User IDs)
        if (isset($validated['participants']) && is_array($validated['participants'])) {
            $validated['participants'] = array_values(array_filter($validated['participants']));
        } else {
            $validated['participants'] = [];
        }

        // ------------------------------------------------------------------
        // A. CEK BENTROK JADWAL KENDARAAN (Hanya Jika Mobil Dipilih)
        // ------------------------------------------------------------------
        if (!empty($validated['vehicle_id'])) {
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
                        'vehicle_id' => 'Mobil yang dipilih sudah memiliki jadwal peminjaman yang disetujui pada rentang tanggal tersebut.'
                    ]);
            }
        }

        // ------------------------------------------------------------------
        // B. CEK BENTROK JADWAL PEMINJAM UTAMA & ANGGOTA TIM
        // ------------------------------------------------------------------
        // Kumpulkan seluruh ID personil (Pemohon Utama + Anggota Tim)
        $allPersonnelIds = array_merge([Auth::id()], $validated['participants']);

        $userOverlap = Booking::whereIn('status', ['approved', 'on_trip'])
            ->where(function ($query) use ($validated) {
                $query->where('start_date', '<', $validated['end_date'])
                    ->where('end_date', '>', $validated['start_date']);
            })
            ->where(function ($query) use ($allPersonnelIds) {
                // A. Cek jika personil menjadi Pemohon Utama di booking lain
                $query->whereIn('user_id', $allPersonnelIds);

                // B. Cek jika personil tersimpan sebagai Anggota Tim di booking lain
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

        // 3. Lengkapi Field Lain & Simpan Booking
        $validated['letter_slug'] = $this->generateUniqueSlug($validated['letter_number']);
        $validated['user_id']     = Auth::id();
        $validated['status']      = 'pending';
        $validated['bast']        = $validated['bast'] ?? '-'; // Isian default awal sebelum BAST diisi pasca-perjalanan

        $booking = Booking::create($validated);

        // ------------------------------------------------------------------
        // C. PENGIRIMAN NOTIFIKASI WHATSAPP
        // ------------------------------------------------------------------
        
        // A. Notifikasi ke Admin App
        $adminPhones = User::where('role', 'admin_lap')->pluck('phone')->filter()->toArray();
        if (!empty($adminPhones)) {
            $msgAdmin = "📩 *PERMOHONAN PEMINJAMAN BARU*\n\n"
                . "No. Surat: {$booking->letter_number}\n"
                . "Pemohon: " . Auth::user()->name . "\n"
                . "Tipe Armada: " . strtoupper($booking->vehicle_type) . "\n"
                . "Tujuan: {$booking->destination}\n"
                . "Jadwal: " . $booking->start_date->format('d/m/Y H:i') . " s.d. " . $booking->end_date->format('d/m/Y H:i') . "\n\n"
                . "Mohon periksa dan proses persetujuan melalui dashboard aplikasi.";

            WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);
        }

        // B. Notifikasi ke Pemohon Utama
        if (Auth::user()->phone) {
            $msgApplicant = "✅ *PENGAJUAN PEMINJAMAN BERHASIL*\n\n"
                . "No. Surat: {$booking->letter_number}\n"
                . "Tujuan: {$booking->destination}\n"
                . "Jadwal: " . $booking->start_date->format('d/m/Y H:i') . " s.d. " . $booking->end_date->format('d/m/Y H:i') . "\n\n"
                . "Permohonan Anda sedang menunggu persetujuan Admin.";

            WhatsAppService::sendMessage(Auth::user()->phone, $msgApplicant);
        }

        // C. Notifikasi ke Anggota Tim
        if (!empty($booking->participants)) {
            $participantPhones = User::whereIn('id', $booking->participants)
                ->pluck('phone')
                ->filter()
                ->toArray();

            if (!empty($participantPhones)) {
                $msgMember = "ℹ️ *INFORMASI PERJALANAN DINAS*\n\n"
                    . "Anda telah didaftarkan oleh *" . Auth::user()->name . "* sebagai anggota tim permohonan peminjaman armada ke *{$booking->destination}* pada tanggal *" . $booking->start_date->format('d/m/Y') . "*.";

                WhatsAppService::sendBulkMessage($participantPhones, $msgMember);
            }
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Pengajuan permohonan peminjaman armada berhasil dikirim.');
    }

    public function show(Booking $booking)
    {
        // 1. Eager load relasi pendukung (termasuk driver yang baru ditambahkan)
        $booking->load(['user', 'vehicle', 'driver', 'log']);

        // 2. Ambil Odometer/KM terakhir kendaraan jika ada
        if ($booking->vehicle) {
            $lastLog = VehicleLog::where('vehicle_id', $booking->vehicle_id)
                ->latest('id')
                ->first();

            $booking->vehicle->last_km = $lastLog 
                ? ($lastLog->end_km ?? $lastLog->start_km ?? 0) 
                : 0;
        }

        return view('bookings.show', compact('booking'));
    }

    public function processReview(Request $request, Booking $booking)
    {
        $request->validate([
            'action'     => 'required|in:approve,reject',
            'vehicle_id' => 'required_if:action,approve|nullable|exists:vehicles,id',
            'driver_id'  => 'nullable|exists:users,id',
            'admin_note' => 'required_if:action,reject|nullable|string|max:500',
        ], [
            'vehicle_id.required_if' => 'Unit armada wajib dipilih saat menyetujui peminjaman.',
            'admin_note.required_if' => 'Alasan penolakan wajib diisi.',
        ]);

        if ($request->action === 'approve') {

            // 🛑 1. VALIDASI BENTROKAN JADWAL ARMADA & DRIVER
            $startDate = $booking->start_date;
            $endDate   = $booking->end_date;

            // Query dasar untuk memfilter booking lain yang irisannya bentrok
            $overlappingQuery = Booking::where('id', '!=', $booking->id)
                ->whereIn('status', ['approved', 'on_trip'])
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                        });
                });

            // A. Cek Bentrokan Armada
            if ($request->filled('vehicle_id')) {
                $isVehicleConflict = (clone $overlappingQuery)
                    ->where('vehicle_id', $request->vehicle_id)
                    ->exists();

                if ($isVehicleConflict) {
                    throw ValidationException::withMessages([
                        'vehicle_id' => 'Unit armada yang dipilih sudah dijadwalkan untuk tugas lain pada rentang waktu tersebut.'
                    ]);
                }
            }

            // B. Cek Bentrokan Driver
            if ($request->filled('driver_id')) {
                $isDriverConflict = (clone $overlappingQuery)
                    ->where('driver_id', $request->driver_id)
                    ->exists();

                if ($isDriverConflict) {
                    throw ValidationException::withMessages([
                        'driver_id' => 'Driver yang dipilih sudah ditugaskan pada perjalanan lain di rentang waktu yang sama.'
                    ]);
                }
            }

            // 2. Update data booking
            $booking->update([
                'status'     => 'approved',
                'vehicle_id' => $request->vehicle_id,
                'driver_id'  => $request->driver_id,
                'admin_note' => $request->admin_note,
                'approved_by'=> Auth::id(),
                'approved_at'=> now(),
            ]);

            // Load relasi terbaru untuk keperluan pesan WA
            $booking->load(['user', 'vehicle', 'driver']);

            $vehicleName = $booking->vehicle 
                ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')' 
                : '-';
                
            $driverName = $booking->driver ? $booking->driver->name : 'Lepas Kunci (Tanpa Driver)';

            // 📩 A. Pengiriman WA ke Pemohon Utama
            if (!empty($booking->user?->phone)) {
                $msgApplicant = "✅ *PERMOHONAN PEMINJAMAN DISETUJUI*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Permohonan peminjaman armada Anda dengan nomor surat *" . $booking->letter_number . "* telah disetujui.\n\n"
                    . "📍 *Tujuan:* " . $booking->destination . "\n"
                    . "📅 *Jadwal:* " . $booking->start_date->format('d/m/Y H:i') . " s/d " . $booking->end_date->format('d/m/Y H:i') . "\n"
                    . "🚗 *Armada:* " . $vehicleName . "\n"
                    . "👤 *Driver:* " . $driverName . "\n\n"
                    . "Selamat bertugas!";

                WhatsAppService::sendMessage($booking->user->phone, $msgApplicant);
            }

            // 📩 B. Pengiriman WA ke Anggota Tim (Participants)
            if (!empty($booking->participants)) {
                $memberPhones = User::whereIn('id', $booking->participants)
                    ->pluck('phone')
                    ->filter()
                    ->toArray();

                if (!empty($memberPhones)) {
                    $msgMember = "📢 *PEMBERITAHUAN PERJALANAN DINAS*\n\n"
                        . "Halo,\nAnda terdaftar sebagai anggota tim dalam perjalanan dinas ke *" . $booking->destination . "*.\n\n"
                        . "📅 *Jadwal:* " . $booking->start_date->format('d/m/Y H:i') . " s/d " . $booking->end_date->format('d/m/Y H:i') . "\n"
                        . "🚗 *Armada:* " . $vehicleName . "\n"
                        . "👤 *Driver:* " . $driverName . "\n\n"
                        . "Mohon bersiap sesuai jadwal keberangkatan.";

                    WhatsAppService::sendBulkMessage($memberPhones, $msgMember);
                }
            }

            // 📩 C. Pengiriman WA ke Driver (Jika Ada)
            if ($booking->driver && !empty($booking->driver->phone)) {
                $msgDriver = "🚖 *TUGAS PENGAWALAN ARMADA BARU*\n\n"
                    . "Halo *" . $booking->driver->name . "*,\n"
                    . "Anda telah ditunjuk sebagai driver untuk perjalanan dinas berikut:\n\n"
                    . "📍 *Tujuan:* " . $booking->destination . "\n"
                    . "📅 *Jadwal:* " . $booking->start_date->format('d/m/Y H:i') . " s/d " . $booking->end_date->format('d/m/Y H:i') . "\n"
                    . "🚗 *Armada:* " . $vehicleName . "\n"
                    . "👤 *Pemohon:* " . $booking->user->name . " (" . ($booking->user->phone ?? '-') . ")\n\n"
                    . "Harap siapkan armada kendaraan tepat waktu.";

                WhatsAppService::sendMessage($booking->driver->phone, $msgDriver);
            }

            return back()->with('success', 'Permohonan berhasil disetujui dan notifikasi WhatsApp telah dikirimkan.');

        } else {
            // 1. Update status menjadi rejected
            $booking->update([
                'status'     => 'rejected',
                'admin_note' => $request->admin_note,
            ]);

            $booking->load('user');

            // 📩 Pengiriman WA Penolakan ke Pemohon Utama
            if (!empty($booking->user?->phone)) {
                $msgReject = "❌ *PERMOHONAN PEMINJAMAN DITOLAK*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Mohon maaf, permohonan peminjaman armada Anda dengan nomor surat *" . $booking->letter_number . "* tidak dapat disetujui.\n\n"
                    . "📝 *Alasan Penolakan:* " . $request->admin_note . "\n\n"
                    . "Silakan hubungi Admin Lapangan untuk informasi lebih lanjut.";

                WhatsAppService::sendMessage($booking->user->phone, $msgReject);
            }

            return back()->with('success', 'Permohonan peminjaman armada telah ditolak dan notifikasi telah dikirim.');
        }
    }

    public function edit(Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // 🛡️ Proteksi Status: Hanya status 'pending' yang boleh di-edit
        if ($booking->status !== 'pending') {
            return redirect()->route('bookings.index')
                ->withErrors(['error' => 'Permohonan ini sudah diproses dan tidak dapat diubah lagi.']);
        }

        // 🛡️ Proteksi Hak Akses: Pemohon Utama atau Admin
        $isAdmin = in_array($currentUser->role, ['admin_app', 'admin_lap']);
        $isOwner = $booking->user_id === $currentUser->id;

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah permohonan ini.');
        }

        // Ambil Data Pendukung
        $vehicles = Vehicle::where('status', '!=', 'maintenance')->where('type', 'monitoring')->get();
        $users    = User::where('id', '!=', $booking->user_id)->get();

        return view('bookings.edit', compact('booking', 'vehicles', 'users'));
    }

    public function update(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // 1. KESEPAKATAN: HANYA STATUS 'PENDING' YANG BISA DI-EDIT
        if ($booking->status !== 'pending') {
            return redirect()->route('bookings.index')
                ->withErrors(['error' => 'Permohonan yang sudah diproses atau disetujui tidak dapat diubah lagi.']);
        }

        // 2. Hak Akses: Pemohon Utama atau Admin
        $isAdmin = in_array($currentUser->role, ['admin_app', 'admin_lap']);
        $isOwner = $booking->user_id === $currentUser->id;

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah permohonan ini.');
        }

        // 3. Validasi Input
        $validated = $request->validate([
            'letter_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('bookings', 'letter_number')->where(function ($query) use ($booking) {
                    return $query->whereNotIn('status', ['canceled', 'rejected'])->where('id', '!=', $booking->id);
                }),
            ],
            'vehicle_type'         => ['required', 'in:operational,monitoring'],
            'vehicle_id'           => ['required_if:vehicle_type,monitoring', 'nullable', 'exists:vehicles,id'],
            'vehicle_requirements' => ['nullable', 'string'],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['required', 'date', 'after:start_date'],
            'destination'          => ['required', 'string', 'max:255'],
            'purpose'              => ['required', 'string'],
            'letter_file'          => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'participants'         => ['nullable', 'array'],
            'participants.*'       => ['nullable', 'exists:users,id'],
        ]);

        // Clean array anggota tim
        $validated['participants'] = isset($validated['participants']) 
            ? array_values(array_filter($validated['participants'])) 
            : [];

        // 4. CEK BENTROK JADWAL KENDARAAN (Jika Vehicle ID Terisi pada tipe Monitoring)
        if (!empty($validated['vehicle_id'])) {
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
        }

        // 5. CEK BENTROK JADWAL PEMINJAM & ANGGOTA TIM
        $allPersonnelIds = array_merge([$booking->user_id], $validated['participants']);

        $userOverlap = Booking::where('id', '!=', $booking->id)
            ->whereIn('status', ['approved', 'on_trip'])
            ->where(function ($query) use ($validated) {
                $query->where('start_date', '<', $validated['end_date'])
                    ->where('end_date', '>', $validated['start_date']);
            })
            ->where(function ($query) use ($allPersonnelIds) {
                $query->whereIn('user_id', $allPersonnelIds);

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

        // 6. Penanganan Upload File Surat Perintah/Tugas
        if ($request->hasFile('letter_file')) {
            if ($booking->letter_file && Storage::disk('public')->exists($booking->letter_file)) {
                Storage::disk('public')->delete($booking->letter_file);
            }
            $validated['letter_file'] = $request->file('letter_file')->store('letters', 'public');
        }

        // Generate slug baru jika nomor surat diubah
        if ($booking->letter_number !== $validated['letter_number']) {
            $validated['letter_slug'] = $this->generateUniqueSlug($validated['letter_number'], $booking->id);
        }

        // 7. Simpan Perubahan (Tanpa Pengiriman WA)
        $booking->update($validated);

        return redirect()->route('bookings.index')->with('success', 'Permohonan peminjaman berhasil diperbarui.');
    }

    public function startTrip(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $isAdmin = in_array($currentUser->role, ['admin_app', 'admin_lap']);
        $isDriver = $booking->driver_id === $currentUser->id;

        if (!$isAdmin && !$isDriver) {
            abort(403, 'Akses ditolak.');
        }

        if ($booking->status !== 'approved') {
            return back()->withErrors(['error' => 'Perjalanan belum disetujui.']);
        }

        $validated = $request->validate([
            'start_km'         => ['required', 'numeric', 'min:0'],
            'start_fuel_level' => ['required', 'string', 'max:20'],
            'start_photo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($request->hasFile('start_photo')) {
            $validated['start_photo'] = $request->file('start_photo')->store('vehicle_logs/starts', 'public');
        }

        // Update Status Booking menjadi 'on_trip'
        $booking->update(['status' => 'on_trip']);

        // Buat Record Log Kendaraan
        VehicleLog::create([
            'booking_id'       => $booking->id,
            'vehicle_id'       => $booking->vehicle_id,
            'checked_out_at'   => now(),
            'start_km'         => $validated['start_km'],
            'start_fuel_level' => $validated['start_fuel_level'],
            'start_photo'      => $validated['start_photo'] ?? null,
        ]);

        // Reload Relasi untuk Data Notifikasi WA
        $booking->load(['user', 'vehicle', 'driver']);

        $vehicleName = $booking->vehicle 
            ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')' 
            : '-';

        // 📩 A. Kirim WA Keberangkatan ke Pemohon Utama
        if (!empty($booking->user?->phone)) {
            $msgApplicant = "🚀 *PERJALANAN DINAS DIMULAI*\n\n"
                . "Halo *" . $booking->user->name . "*,\n"
                . "Perjalanan dinas Anda ke *" . $booking->destination . "* resmi dimulai.\n\n"
                . "🚗 *Armada:* " . $vehicleName . "\n"
                . "📊 *KM Awal:* " . number_format($validated['start_km'], 0, ',', '.') . " KM\n"
                . "⛽ *BBM Awal:* " . $validated['start_fuel_level'] . "\n\n"
                . "Selamat sampai tujuan dan utamakan keselamatan berkendara!";

            WhatsAppService::sendMessage($booking->user->phone, $msgApplicant);
        }

        // 📩 B. Kirim WA Keberangkatan ke Anggota Tim (Participants)
        if (!empty($booking->participants)) {
            $memberPhones = User::whereIn('id', $booking->participants)
                ->pluck('phone')
                ->filter()
                ->toArray();

            if (!empty($memberPhones)) {
                $msgMember = "🚀 *PERJALANAN DINAS DIMULAI*\n\n"
                . "Halo,\n"
                . "Perjalanan dinas Anda ke *" . $booking->destination . "* resmi dimulai.\n\n"
                . "🚗 *Armada:* " . $vehicleName . "\n"
                . "📊 *KM Awal:* " . number_format($validated['start_km'], 0, ',', '.') . " KM\n"
                . "⛽ *BBM Awal:* " . $validated['start_fuel_level'] . "\n\n"
                . "Selamat sampai tujuan dan utamakan keselamatan berkendara!";

                WhatsAppService::sendBulkMessage($memberPhones, $msgMember);
            }
        }

        return redirect()->route('bookings.index')->with('success', 'Perjalanan dinas berhasil dimulai dan notifikasi telah dikirim.');
    }

    public function completeTrip(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $isAdmin = in_array($currentUser->role, ['admin_app', 'admin_lap']);
        $isDriver = $booking->driver_id === $currentUser->id;

        if (!$isAdmin && !$isDriver) {
            abort(403, 'Akses ditolak.');
        }

        if ($booking->status !== 'on_trip') {
            return back()->withErrors(['error' => 'Status perjalanan tidak valid untuk diselesaikan.']);
        }

        $log = VehicleLog::where('booking_id', $booking->id)->firstOrFail();

        $validated = $request->validate([
            'end_km'             => ['required', 'numeric', 'gte:' . $log->start_km],
            'end_fuel_level'     => ['required', 'string', 'max:20'],
            'end_photo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'condition_notes'    => ['nullable', 'string'],
            'condition_photos'   => ['nullable', 'array'],
            'condition_photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        // Kalkulasi Jarak Tempuh
        $distanceTraveled = $validated['end_km'] - $log->start_km;

        if ($request->hasFile('end_photo')) {
            $validated['end_photo'] = $request->file('end_photo')->store('vehicle_logs/ends', 'public');
        }

        // Upload Foto Insiden jika Ada
        $uploadedConditionPhotos = [];
        if ($request->hasFile('condition_photos')) {
            foreach ($request->file('condition_photos') as $photo) {
                $uploadedConditionPhotos[] = $photo->store('vehicle_logs/incidents', 'public');
            }
        }

        // Update Log Kendaraan
        $log->update([
            'checked_in_at'     => now(),
            'end_km'            => $validated['end_km'],
            'end_fuel_level'    => $validated['end_fuel_level'],
            'end_photo'         => $validated['end_photo'] ?? null,
            'distance_traveled' => $distanceTraveled,
            'condition_notes'   => $validated['condition_notes'] ?? null,
            'condition_photos'  => !empty($uploadedConditionPhotos) ? json_encode($uploadedConditionPhotos) : null,
        ]);

        // Update Status Booking menjadi 'completed'
        $booking->update(['status' => 'completed']);

        // Reload Relasi untuk Data Notifikasi WA
        $booking->load(['user', 'vehicle']);

        $vehicleName = $booking->vehicle 
            ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')' 
            : '-';

        // 📩 A. Kirim WA Penyelesaian ke Pemohon Utama
        if (!empty($booking->user?->phone)) {
            $msgApplicant = "🏁 *PERJALANAN DINAS SELESAI*\n\n"
                . "Halo *" . $booking->user->name . "*,\n"
                . "Perjalanan dinas Anda ke *" . $booking->destination . "* telah selesai dilakukan dan kendaraan telah dikembalikan.\n\n"
                . "🚗 *Armada:* " . $vehicleName . "\n"
                . "📏 *Total Jarak Tempuh:* " . number_format($distanceTraveled, 0, ',', '.') . " KM\n"
                . "⛽ *BBM Akhir:* " . $validated['end_fuel_level'] . "\n\n"
                . "Terima kasih atas kerja samanya.";

            WhatsAppService::sendMessage($booking->user->phone, $msgApplicant);
        }

        // 📩 B. Kirim WA Penyelesaian ke Anggota Tim (Participants)
        if (!empty($booking->participants)) {
            $memberPhones = User::whereIn('id', $booking->participants)
                ->pluck('phone')
                ->filter()
                ->toArray();

            if (!empty($memberPhones)) {
                $msgMember = "🏁 *PERJALANAN DINAS SELESAI*\n\n"
                . "Halo,\n"
                . "Perjalanan dinas Anda ke *" . $booking->destination . "* telah selesai dilakukan dan kendaraan telah dikembalikan.\n\n"
                . "🚗 *Armada:* " . $vehicleName . "\n"
                . "📏 *Total Jarak Tempuh:* " . number_format($distanceTraveled, 0, ',', '.') . " KM\n"
                . "⛽ *BBM Akhir:* " . $validated['end_fuel_level'] . "\n\n"
                . "Terima kasih atas kerja samanya.";

                WhatsAppService::sendBulkMessage($memberPhones, $msgMember);
            }
        }

        return redirect()->route('bookings.index')->with('success', 'Perjalanan dinas telah diselesaikan dan notifikasi telah dikirimkan.');
    }

    public function cancelBooking(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Guard: Hanya pemilik booking yang bisa membatalkan
        if ($booking->user_id !== $currentUser->id && !in_array($currentUser->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk membatalkan pengajuan ini.');
        }

        // Guard: Hanya bisa dibatalkan jika masih pending
        if ($booking->status !== 'pending') {
            return back()->withErrors(['error' => 'Permohonan yang sudah diproses tidak dapat dibatalkan langsung oleh pemohon.']);
        }

        $booking->update([
            'status' => 'canceled',
        ]);

        // 📩 WA Notifikasi ke Pemohon
        if (!empty($booking->user?->phone)) {
            $msg = "🚫 *PEMBATALAN PENGAJUAN PEMINJAMAN*\n\n"
                . "Halo *" . $booking->user->name . "*,\n"
                . "Pengajuan peminjaman armada Anda dengan nomor surat *" . $booking->letter_number . "* ke *" . $booking->destination . "* telah berhasil dibatalkan.";

            WhatsAppService::sendMessage($booking->user->phone, $msg);
        }

        return back()->with('success', 'Pengajuan peminjaman berhasil dibatalkan.');
    }

    public function cancelApproval(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Guard: Hanya Admin
        if (!in_array($currentUser->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Akses ditolak.');
        }

        // Guard: Hanya bisa membatalkan persetujuan jika status masih approved
        if ($booking->status !== 'approved') {
            return back()->withErrors(['error' => 'Hanya permohonan berstatus "approved" yang dapat dibatalkan persetujuannya.']);
        }

        $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ], [
            'cancel_reason.required' => 'Alasan pembatalan persetujuan wajib diisi.',
        ]);

        $booking->load(['user', 'vehicle', 'driver']);

        // Simpan data temporary untuk pesan WA
        $vehicleName = $booking->vehicle ? ($booking->vehicle->brand . ' ' . $booking->vehicle->model) : '-';
        $driverUser  = $booking->driver;

        // Reset status & lepaskan alokasi armada/driver
        $booking->update([
            'status'     => 'canceled',
            'vehicle_id' => null,
            'driver_id'  => null,
            'admin_note' => 'BATAL PERSETUJUAN: ' . $request->cancel_reason,
        ]);

        // 📩 A. WA Notifikasi ke Pemohon Utama
        if (!empty($booking->user?->phone)) {
            $msgApplicant = "⚠️ *PERSETUJUAN PEMINJAMAN DIBATALKAN*\n\n"
                . "Halo *" . $booking->user->name . "*,\n"
                . "Mohon maaf, persetujuan peminjaman armada dengan nomor surat *" . $booking->letter_number . "* telah dibatalkan oleh Pengelola Armada.\n\n"
                . "📝 *Alasan Pembatalan:* " . $request->cancel_reason;

            WhatsAppService::sendMessage($booking->user->phone, $msgApplicant);
        }

        // 📩 B. WA Notifikasi ke Driver (jika sebelumnya sudah ditunjuk)
        if ($driverUser && !empty($driverUser->phone)) {
            $msgDriver = "ℹ️ *PEMBATALAN TUGAS DRIVER*\n\n"
                . "Halo *" . $driverUser->name . "*,\n"
                . "Penugasan driver untuk perjalanan dinas ke *" . $booking->destination . "* (" . $booking->letter_number . ") telah dibatalkan oleh Admin.";

            WhatsAppService::sendMessage($driverUser->phone, $msgDriver);
        }

        return back()->with('success', 'Persetujuan peminjaman berhasil dibatalkan dan alokasi armada/driver telah dilepaskan.');
    }
}