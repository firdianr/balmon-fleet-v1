<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Mail\BookingCompletedMail;
use App\Models\Booking;
use App\Models\BookingSignature;
use App\Models\DraftBooking;
use App\Models\DriverAttendance;
use App\Models\MasterChecklist;
use App\Models\TripChecklist;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Query Data Peminjaman
        $bookings = Booking::with(['user', 'vehicle.checklists', 'driver', 'log', 'checklists'])
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

        // 2. Data Master Driver & Pegawai Non-Driver
        $primaryDrivers = User::where('role', 'driver')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'role']);
            
        $nonDrivers = User::where('role', '!=', 'driver')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'role']);
            
        $availableDrivers = $primaryDrivers->concat($nonDrivers)->map(function ($d) {
            return [
                'id'   => $d->id,
                'name' => $d->name,
                'role' => $d->role,
            ];
        });

        // 3. Data Master Kendaraan Non-Maintenance
        $availableVehicles = Vehicle::where('status', '!=', 'maintenance')
            ->orderBy('code', 'asc')
            ->get(['id', 'brand', 'model', 'type', 'drivetrain', 'plate_number', 'code', 'photo']);

        // 4. Data Master Checklist Aktif (JSON Snapshot)
        $masterChecklists = MasterChecklist::active()
            ->get(['id', 'name', 'category', 'sort_order'])
            ->toArray();

        // 5. Dataset Jadwal Booking Aktif (Untuk Filter Realtime Alpine.js)
        $activeBookings = Booking::whereIn('status', ['waiting', 'approved', 'on_trip', 'returned', 'checked'])
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

        // 5.5 Dataset Ketidakhadiran Driver (Terbuka/Aktif & Approved)
        $attendances = DriverAttendance::where('process_status', 'approved')
            ->where('end_date', '>=', Carbon::today())
            ->get(['id', 'user_id', 'start_date', 'end_date', 'status'])
            ->map(function ($att) {
                return [
                    'id'         => $att->id,
                    'driver_id'  => $att->user_id,
                    'start_date' => $att->start_date->format('Y-m-d H:i:s'),
                    'end_date'   => $att->end_date->format('Y-m-d H:i:s'),
                    'status'     => $att->status,
                ];
            });

        // 6. Flag Reassign Armada Maintenance
        $today = now()->startOfDay();

        $bookings->getCollection()->transform(function ($booking) use ($today) {
            $booking->needs_reassign = in_array($booking->status, ['waiting', 'approved'])
                && ($booking->vehicle?->status === 'maintenance')
                && ($booking->start_date >= $today);

            return $booking;
        });

        return view('bookings.index', compact(
            'bookings', 
            'availableVehicles', 
            'availableDrivers', 
            'primaryDrivers',
            'nonDrivers',
            'activeBookings',
            'attendances', // Pass data ketidakhadiran ke View
            'masterChecklists'
        ));
    }

    public function create()
    {
        $users = User::whereIn('role', ['admin_lap', 'pegawai', 'driver', 'admin_app', 'approver'])
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'nip', 'jabatan', 'role']);

        $monitoringVehicles = Vehicle::where('type', 'monitoring')
            ->where('status', '!=', 'maintenance')
            ->orderBy('code', 'asc')
            ->get();

        $operationalVehicles = Vehicle::where('type', 'operational')
            ->where('status', '!=', 'maintenance')
            ->orderBy('code', 'asc')
            ->get();

        return view('bookings.create', compact('users', 'monitoringVehicles', 'operationalVehicles'));
    }

    public function createFromDraft(DraftBooking $draftBooking)
    {
        // Pastikan draft berstatus approved
        if ($draftBooking->status !== 'approved') {
            return redirect()->route('draft-bookings.index')
                ->with('error', 'Draft permohonan harus disetujui terlebih dahulu sebelum dilanjutkan.');
        }

        $draftBooking->load(['user', 'vehicle', 'driver']);

        $users = User::whereIn('role', ['admin_lap', 'pegawai', 'driver', 'admin_app', 'approver'])
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'nip', 'jabatan', 'role']);

        return view('bookings.create_from_draft', compact('draftBooking', 'users'));
    }

    public function parseDocument(Request $request)
    {
        $request->validate([
            'document'      => 'required|file|mimes:pdf|max:5120',
            'document_type' => 'nullable|in:surat_tugas,nota_dinas',
        ]);

        try {
            $file = $request->file('document');
            $filePath = $file->store('letters', 'public');
            $docType  = $request->input('document_type', 'surat_tugas');
            $typeName = ($docType === 'nota_dinas') ? 'Nota Dinas' : 'Surat Tugas';

            $pdfBase64 = base64_encode(file_get_contents($file->getPathname()));

            $prompt = "Kamu adalah sistem ekstraktor dokumen resmi pemerintah. Analisis dokumen {$typeName} dalam berkas PDF ini dan ekstrak datanya ke format JSON murni.

            Data yang harus diekstrak:
            1. letter_number: Nomor {$typeName} lengkap.
            2. destination: Tempat/Lokasi/Tujuan pelaksanaan kegiatan.
            3. purpose: Maksud/Hal/Keperluan agenda kegiatan.
            4. start_date: Tanggal/waktu mulai pelaksanaan (format YYYY-MM-DD HH:mm:ss atau YYYY-MM-DD).
            5. end_date: Tanggal/waktu selesai pelaksanaan (format YYYY-MM-DD HH:mm:ss atau YYYY-MM-DD).
            6. nips: Array berisi daftar NIP (18 digit angka) dari pegawai yang ditugaskan/tercantum.";

            $apiKey = config('services.gemini.api_key');
            $model  = config('services.gemini.model', 'gemini-flash-latest');

            $response = Http::timeout(60)
                ->retry(2, 1000)
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

            $matchedUserIds = [];
            if (!empty($aiResult['nips']) && is_array($aiResult['nips'])) {
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
                'message' => 'Gagal membaca dokumen via AI: ' . $e->getMessage()
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
        $validated = $request->validated();

        if ($request->hasFile('letter_file')) {
            $validated['letter_file'] = $request->file('letter_file')->store('letters', 'public');
        } elseif ($request->filled('letter_file_temp')) {
            $validated['letter_file'] = $request->input('letter_file_temp');
        }

        // Tangkap jenis dokumen (surat_tugas / nota_dinas)
        $validated['letter_type'] = $request->input('letter_type', 'surat_tugas');

        if (isset($validated['participants']) && is_array($validated['participants'])) {
            $validated['participants'] = array_values(array_filter($validated['participants']));
        } else {
            $validated['participants'] = [];
        }

        if ($validated['vehicle_type'] === 'operational') {
            $validated['vehicle_id'] = null;
        }

        // Cek bentrok alokasi kendaraan
        if (!empty($validated['vehicle_id'])) {
            $isVehicleOverlapped = Booking::where('vehicle_id', $validated['vehicle_id'])
                ->whereIn('status', ['waiting', 'approved', 'on_trip', 'returned', 'checked'])
                ->where(function ($query) use ($validated) {
                    $query->where('start_date', '<', $validated['end_date'])
                        ->where('end_date', '>', $validated['start_date']);
                })
                ->exists();

            if ($isVehicleOverlapped) {
                return back()
                    ->withInput($request->except('letter_file') + ['letter_file_temp' => $validated['letter_file'] ?? null])
                    ->withErrors([
                        'vehicle_id' => 'Mobil yang dipilih sudah memiliki jadwal peminjaman yang disetujui pada rentang waktu tersebut.'
                    ]);
            }
        }

        // Cek bentrok jadwal personel
        $allPersonnelIds = array_merge([Auth::id()], $validated['participants']);
        $userOverlap = Booking::whereIn('status', ['waiting', 'approved', 'on_trip', 'returned', 'checked'])
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
            return back()
                ->withInput($request->except('letter_file') + ['letter_file_temp' => $validated['letter_file'] ?? null])
                ->withErrors([
                    'participants' => 'Anda atau salah melebihkan anggota tim yang dipilih sudah memiliki jadwal perjalanan dinas lain pada rentang waktu ini.'
                ]);
        }

        // Validasi Keberadaan Tanda Tangan
        if (!$request->filled('signature')) {
            return back()
                ->withInput($request->except('letter_file') + ['letter_file_temp' => $validated['letter_file'] ?? null])
                ->withErrors([
                    'signature' => 'Tanda tangan pemohon wajib dibubuhkan sebelum mengirimkan permohonan.'
                ]);
        }

        $validated['letter_slug'] = $this->generateUniqueSlug($validated['letter_number']);
        $validated['user_id']     = Auth::id();
        $validated['status']      = 'pending';

        // Jalankan transaksi database
        $booking = DB::transaction(function () use ($validated, $request) {
            // 1. Simpan Data Booking Utama
            $booking = Booking::create($validated);

            // 2. Dekode String Base64 Menjadi File PNG Fisik
            $base64Image = $request->input('signature');
            
            // Pisahkan header "data:image/png;base64," dari payload datanya
            @list(, $fileData) = explode(';', $base64Image);
            @list(, $fileData) = explode(',', $fileData);
            $decodedImage = base64_decode($fileData);

            // Buat nama file PNG yang unik
            $fileName = 'signatures/sig_' . $booking->id . '_' . Str::random(10) . '.png';

            // Simpan ke storage disk 'public' (storage/app/public/signatures/...)
            Storage::disk('public')->put($fileName, $decodedImage);

            // 3. Simpan Path File PNG ke DB untuk 2 Jenis Dokumen (Formulir & BAST)
            $documentsToSign = ['formulir_peminjaman'];

            foreach ($documentsToSign as $docType) {
                BookingSignature::create([
                    'booking_id'     => $booking->id,
                    'user_id'        => Auth::id(),
                    'document_type'  => $docType,
                    'signer_role'    => 'pemohon',
                    'signature_path' => $fileName, // Menyimpan path file PNG (misal: "signatures/sig_1_x82js.png")
                    'signed_at'      => now(),
                ]);
            }

            return $booking;
        });

        // Kirim Notifikasi WhatsApp
        $adminPhones = User::where('role', 'admin_lap')->pluck('phone')->filter()->toArray();
        if (!empty($adminPhones)) {
            $startDateStr = \Carbon\Carbon::parse($booking->start_date)->format('d/m/Y H:i');
            $endDateStr   = \Carbon\Carbon::parse($booking->end_date)->format('d/m/Y H:i');
            $docLabel     = $booking->letter_type === 'nota_dinas' ? 'Nota Dinas' : 'Surat Tugas';

            $msgAdmin = "📩 *PERMOHONAN PEMINJAMAN BARU*\n\n"
                . "No. {$docLabel}: {$booking->letter_number}\n"
                . "Pemohon: " . Auth::user()->name . "\n"
                . "Tipe Armada: " . strtoupper($booking->vehicle_type) . "\n"
                . "Tujuan: {$booking->destination}\n"
                . "Jadwal: {$startDateStr} s.d. {$endDateStr}\n\n"
                . "Mohon lakukan alokasi armada dan driver via aplikasi Balmon-Fleet.";

            WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Pengajuan permohonan peminjaman armada berhasil dikirim.');
    }

    public function storeFromDraft(StoreBookingRequest $request, DraftBooking $draftBooking)
    {
        $validated = $request->validated();

        if ($request->hasFile('letter_file')) {
            $validated['letter_file'] = $request->file('letter_file')->store('letters', 'public');
        } elseif ($request->filled('letter_file_temp')) {
            $validated['letter_file'] = $request->input('letter_file_temp');
        }

        $validated['participants'] = isset($validated['participants']) && is_array($validated['participants'])
            ? array_values(array_filter($validated['participants']))
            : [];

        // Validasi Keberadaan Tanda Tangan
        if (!$request->filled('signature')) {
            return back()
                ->withInput($request->except('letter_file') + ['letter_file_temp' => $validated['letter_file'] ?? null])
                ->withErrors([
                    'signature' => 'Tanda tangan pemohon wajib dibubuhkan sebelum mengonversi permohonan.'
                ]);
        }

        // Kunci nilai dari Draft & tangkap letter_type
        $validated['draft_booking_id'] = $draftBooking->id;
        $validated['letter_type']      = $request->input('letter_type', 'surat_tugas');
        $validated['vehicle_id']       = $draftBooking->vehicle_id;
        $validated['driver_id']        = $draftBooking->driver_id;
        $validated['vehicle_type']     = $draftBooking->vehicle ? $draftBooking->vehicle->type : 'operational';
        $validated['letter_slug']      = $this->generateUniqueSlug($validated['letter_number']);
        $validated['user_id']          = $draftBooking->user_id;
        $validated['status']           = 'pending';

        // Transaksi Database untuk Booking & Penyimpanan TTD
        $booking = DB::transaction(function () use ($validated, $draftBooking, $request) {
            // 1. Buat Peminjaman Resmi dari Draft
            $booking = Booking::create($validated);

            // 2. Ubah status Draft menjadi converted
            $draftBooking->update(['status' => 'converted']);

            // 3. Dekode String Base64 Signature Menjadi File PNG Fisik
            $base64Image = $request->input('signature');
            @list(, $fileData) = explode(';', $base64Image);
            @list(, $fileData) = explode(',', $fileData);
            $decodedImage = base64_decode($fileData);

            $fileName = 'signatures/sig_' . $booking->id . '_' . Str::random(10) . '.png';
            Storage::disk('public')->put($fileName, $decodedImage);

            // 4. Simpan Path TTD ke DB untuk Formulir & BAST Peminjaman
            $documentsToSign = ['formulir_peminjaman'];

            foreach ($documentsToSign as $docType) {
                BookingSignature::create([
                    'booking_id'     => $booking->id,
                    'user_id'        => Auth::id(),
                    'document_type'  => $docType,
                    'signer_role'    => 'pemohon',
                    'signature_path' => $fileName,
                    'signed_at'      => now(),
                ]);
            }

            return $booking;
        });

        // Kirim Notifikasi WhatsApp
        $adminPhones = User::where('role', 'admin_lap')->pluck('phone')->filter()->toArray();
        if (!empty($adminPhones)) {
            $startDateStr  = \Carbon\Carbon::parse($booking->start_date)->format('d/m/Y H:i');
            $endDateStr    = \Carbon\Carbon::parse($booking->end_date)->format('d/m/Y H:i');
            $docLabel      = $booking->letter_type === 'nota_dinas' ? 'Nota Dinas' : 'Surat Tugas';
            $applicantName = $draftBooking->user->name ?? Auth::user()->name;

            $msgAdmin = "📋 *PERMOHONAN PEMINJAMAN BARU*\n\n"
                . "No. {$docLabel}: {$booking->letter_number}\n"
                . "Pemohon: {$applicantName}\n"
                . "Tujuan: {$booking->destination}\n"
                . "Jadwal: {$startDateStr} s.d. {$endDateStr}\n\n"
                . "Permohonan peminjaman baru telah masuk. Mohon Tinjau dan lakukan alokasi armada & driver via aplikasi Balmon-Fleet.";

            WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Permohonan peminjaman resmi berhasil dibuat dari Draft.');
    }

    public function show(Booking $booking)
    {
        // Eager load relasi log dan checklists agar query efisien (1-2 query saja)
        $booking->load([
            'user', 
            'vehicle', 
            'driver', 
            'log', 
            'checklists' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        // Ambil checklist spesifik yang terikat dengan log aktif
        $logId = $booking->log?->id;
        
        $startChecklist = $booking->checklists
            ->where('type', 'start')
            ->when($logId, fn($c) => $c->where('vehicle_log_id', $logId))
            ->first() ?? $booking->checklists->where('type', 'start')->last();

        $endChecklist = $booking->checklists
            ->where('type', 'end')
            ->when($logId, fn($c) => $c->where('vehicle_log_id', $logId))
            ->first() ?? $booking->checklists->where('type', 'end')->last();

        // Kirimkan variabel ke view/modal
        return view('bookings.show', compact('booking', 'startChecklist', 'endChecklist'));
    }

    /**
     * TAHAP 1: ALOKASI OLEH ADMIN LAPANGAN (pending -> waiting)
     */
    public function processReview(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Akses ditolak. Hanya Admin Lapangan yang dapat melakukan alokasi.');
        }

        $request->validate([
            'action'     => 'required|in:approve,reject',
            'vehicle_id' => 'required_if:action,approve|nullable|exists:vehicles,id',
            'driver_id'  => 'nullable|exists:users,id',
            'admin_note' => 'required_if:action,reject|nullable|string|max:500',
            'signature'  => 'nullable|string',
        ], [
            'vehicle_id.required_if' => 'Unit armada wajib dipilih saat melakukan alokasi.',
            'admin_note.required_if' => 'Alasan penolakan wajib diisi.',
        ]);

        if ($request->action === 'approve') {
            $startDate = $booking->start_date;
            $endDate   = $booking->end_date;

            // Cek Bentrok Armada & Driver
            $overlappingQuery = Booking::where('id', '!=', $booking->id)
                ->whereIn('status', ['waiting', 'approved', 'on_trip', 'returned', 'checked'])
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                        });
                });

            if ($request->filled('vehicle_id')) {
                $isVehicleConflict = (clone $overlappingQuery)
                    ->where('vehicle_id', $request->vehicle_id)
                    ->exists();

                if ($isVehicleConflict) {
                    throw ValidationException::withMessages([
                        'vehicle_id' => 'Unit armada yang dipilih sudah dialokasikan pada tugas lain di rentang waktu tersebut.'
                    ]);
                }
            }

            if ($request->filled('driver_id')) {
                $isDriverConflict = (clone $overlappingQuery)
                    ->where('driver_id', $request->driver_id)
                    ->exists();

                if ($isDriverConflict) {
                    throw ValidationException::withMessages([
                        'driver_id' => 'Driver yang dipilih sudah ditugaskan pada perjalanan lain di rentang waktu tersebut.'
                    ]);
                }
            }

            // Cek keberadaan record TTD lama
            $existingSignature = BookingSignature::where('booking_id', $booking->id)
                ->where('document_type', 'bast_peminjaman')
                ->where('signer_role', 'admin_lap')
                ->first();

            // Validasi wajib TTD jika TTD lama belum ada dan canvas kosong
            if (!$existingSignature && !$request->filled('signature')) {
                throw ValidationException::withMessages([
                    'signature' => 'Tanda tangan pengesahan Admin Lapangan wajib dibubuhkan.'
                ]);
            }

            // Jalankan Transaksi Database
            DB::transaction(function () use ($booking, $request, $currentUser, $existingSignature) {
                // 1. Update data booking
                $booking->update([
                    'status'       => 'waiting',
                    'vehicle_id'   => $request->vehicle_id,
                    'driver_id'    => $request->driver_id,
                    'admin_note'   => $request->admin_note,
                    'allocated_by' => $currentUser->id,
                    'allocated_at' => now(),
                ]);

                // 2. Simpan/Perbarui TTD jika ada goresan TTD baru dari Canvas
                if ($request->filled('signature')) {
                    // Hapus file PNG lama dari storage jika sebelumnya sudah ada
                    if ($existingSignature && $existingSignature->signature_path) {
                        if (Storage::disk('public')->exists($existingSignature->signature_path)) {
                            Storage::disk('public')->delete($existingSignature->signature_path);
                        }
                    }

                    // Dekode Base64 & Simpan File PNG Baru
                    $base64Image = $request->input('signature');
                    @list(, $fileData) = explode(';', $base64Image);
                    @list(, $fileData) = explode(',', $fileData);
                    $decodedImage = base64_decode($fileData);

                    $fileName = 'signatures/sig_admin_' . $booking->id . '_' . Str::random(10) . '.png';
                    Storage::disk('public')->put($fileName, $decodedImage);

                    // Update atau buat data TTD di DB
                    BookingSignature::updateOrCreate(
                        [
                            'booking_id'    => $booking->id,
                            'document_type' => 'bast_peminjaman',
                            'signer_role'   => 'admin_lap',
                        ],
                        [
                            'user_id'        => $currentUser->id,
                            'signature_path' => $fileName,
                            'signed_at'      => now(),
                        ]
                    );
                }
            });

            $booking->load(['user', 'vehicle', 'driver']);

            $vehicleName = $booking->vehicle 
                ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')' 
                : '-';
                
            $driverName = $booking->driver ? $booking->driver->name : 'Lepas Kunci (Tanpa Driver)';

            // Notifikasi WhatsApp ke Approver
            $approverPhones = User::where('role', 'approver')->pluck('phone')->filter()->toArray();
            if (!empty($approverPhones)) {
                $msgApprover = "📋 *PERMOHONAN MENUNGGU PERSETUJUAN (ALOKASI SELESAI)*\n\n"
                    . "No. Surat: *" . $booking->letter_number . "*\n"
                    . "Pemohon: *" . ($booking->user->name ?? '-') . "*\n"
                    . "Armada: *" . $vehicleName . "*\n"
                    . "Driver: *" . $driverName . "*\n\n"
                    . "Mohon periksa dan berikan persetujuan pada aplikasi Balmon-Fleet.";

                WhatsAppService::sendBulkMessage($approverPhones, $msgApprover);
            }

            return back()->with('success', 'Armada & Driver berhasil dialokasikan. Permohonan kini menunggu persetujuan Atasan/Approver.');

        } else {
            // Alur Penolakan (Reject)
            $booking->update([
                'status'     => 'rejected',
                'admin_note' => $request->admin_note,
            ]);

            if (!empty($booking->user?->phone)) {
                $msgReject = "❌ *PERMOHONAN PEMINJAMAN DITOLAK*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Mohon maaf, permohonan peminjaman armada Anda dengan nomor surat *" . $booking->letter_number . "* tidak dapat disetujui.\n\n"
                    . "📝 *Alasan Penolakan:* " . $request->admin_note;

                WhatsAppService::sendMessage($booking->user->phone, $msgReject);
            }

            return back()->with('success', 'Permohonan peminjaman armada telah ditolak.');
        }
    }

    /**
     * TAHAP 2 & TAHAP 6: PERSETUJUAN OLEH APPROVER (waiting -> approved & checked -> completed)
     */
    public function processApproverReview(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['approver', 'admin_app'])) {
            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang Pejabat/Approver.');
        }

        $validated = $request->validate([
            'action'     => ['required', 'in:approve,reject'],
            'admin_note' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
            'signature'  => ['nullable', 'string'],
        ]);

        $action = $validated['action'];
        $note   = $validated['admin_note'] ?? null;

        if ($action === 'reject') {
            $booking->update([
                'status'     => 'rejected',
                'admin_note' => $note,
            ]);

            if (!empty($booking->user?->phone)) {
                $msg = "❌ *PERMOHONAN PEMINJAMAN DITOLAK*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Permohonan dengan No. Surat *" . $booking->letter_number . "* telah ditolak oleh Pejabat Berwenang.\n"
                    . "📝 *Alasan:* " . ($note ?? '-');

                WhatsAppService::sendMessage($booking->user->phone, $msg);
            }

            return redirect()->route('bookings.index')->with('success', 'Permohonan telah ditolak.');
        }

        // Tentukan jenis dokumen BAST berdasarkan status booking saat ini
        $targetDocType = ($booking->status === 'waiting') ? 'bast_peminjaman' : 'bast_pengembalian';

        // Cek keberadaan TTD lama untuk dokumen target oleh role approver
        $existingSignature = BookingSignature::where('booking_id', $booking->id)
            ->where('document_type', $targetDocType)
            ->where('signer_role', 'approver')
            ->first();

        // Validasi keberadaan TTD jika belum ada TTD lama DAN canvas diisi kosong
        if (!$existingSignature && !$request->filled('signature')) {
            throw ValidationException::withMessages([
                'signature' => 'Tanda tangan pengesahan Approver wajib dibubuhkan.'
            ]);
        }

        // TAHAP 2: PERSETUJUAN KEBERANGKATAN (waiting -> approved)
        if ($booking->status === 'waiting') {
            DB::transaction(function () use ($booking, $request, $currentUser, $note, $targetDocType, $existingSignature) {
                $booking->update([
                    'status'      => 'approved',
                    'approved_by' => $currentUser->id,
                    'approved_at' => now(),
                    'admin_note'  => $note ?? $booking->admin_note,
                ]);

                // Simpan / Update TTD jika ada input signature baru
                if ($request->filled('signature')) {
                    if ($existingSignature && $existingSignature->signature_path) {
                        if (Storage::disk('public')->exists($existingSignature->signature_path)) {
                            Storage::disk('public')->delete($existingSignature->signature_path);
                        }
                    }

                    $base64Image = $request->input('signature');
                    @list(, $fileData) = explode(';', $base64Image);
                    @list(, $fileData) = explode(',', $fileData);
                    $decodedImage = base64_decode($fileData);

                    $fileName = 'signatures/sig_approver_' . $booking->id . '_' . $targetDocType . '_' . Str::random(8) . '.png';
                    Storage::disk('public')->put($fileName, $decodedImage);

                    BookingSignature::updateOrCreate(
                        [
                            'booking_id'    => $booking->id,
                            'document_type' => $targetDocType,
                            'signer_role'   => 'approver',
                        ],
                        [
                            'user_id'        => $currentUser->id,
                            'signature_path' => $fileName,
                            'signed_at'      => now(),
                        ]
                    );
                }
            });

            $booking->load(['user', 'vehicle', 'driver']);

            $vehicleName = $booking->vehicle 
                ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')' 
                : '-';

            if (!empty($booking->user?->phone)) {
                $msgApplicant = "✅ *PERMOHONAN PEMINJAMAN DISETUJUI*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Permohonan armada Anda (" . $booking->letter_number . ") telah *DISETUJUI/APPROVED* oleh Pejabat Berwenang.\n\n"
                    . "🚘 *Armada:* " . $vehicleName . "\n"
                    . "👤 *Driver:* " . ($booking->driver?->name ?? 'Lepas Kunci') . "\n\n"
                    . "Selamat bertugas!";

                WhatsAppService::sendMessage($booking->user->phone, $msgApplicant);
            }

            if ($booking->driver && !empty($booking->driver->phone)) {
                $msgDriver = "🚖 *PENUGASAN DRIVER DISETUJUI*\n\n"
                    . "Halo *" . $booking->driver->name . "*,\n"
                    . "Penugasan driver untuk permohonan *" . $booking->letter_number . "* ke *" . $booking->destination . "* telah disetujui.\n"
                    . "Mohon bersiap sesuai jadwal.";

                WhatsAppService::sendMessage($booking->driver->phone, $msgDriver);
            }

            return redirect()->route('bookings.index')->with('success', 'Permohonan berhasil disetujui. Armada siap digunakan.');
        }

        // TAHAP 5: PERSETUJUAN PENGEMBALIAN / PENGESAHAN BAST (checked -> completed)
        if ($booking->status === 'checked') {
            DB::transaction(function () use ($booking, $request, $currentUser, $note, $targetDocType, $existingSignature) {
                $booking->update([
                    'status'               => 'completed',
                    'returned_approved_by' => $currentUser->id,
                    'returned_approved_at' => now(),
                    'admin_note'           => $note ?? $booking->admin_note,
                ]);

                // Simpan / Update TTD jika ada input signature baru
                if ($request->filled('signature')) {
                    if ($existingSignature && $existingSignature->signature_path) {
                        if (Storage::disk('public')->exists($existingSignature->signature_path)) {
                            Storage::disk('public')->delete($existingSignature->signature_path);
                        }
                    }

                    $base64Image = $request->input('signature');
                    @list(, $fileData) = explode(';', $base64Image);
                    @list(, $fileData) = explode(',', $fileData);
                    $decodedImage = base64_decode($fileData);

                    $fileName = 'signatures/sig_approver_' . $booking->id . '_' . $targetDocType . '_' . Str::random(8) . '.png';
                    Storage::disk('public')->put($fileName, $decodedImage);

                    BookingSignature::updateOrCreate(
                        [
                            'booking_id'    => $booking->id,
                            'document_type' => $targetDocType,
                            'signer_role'   => 'approver',
                        ],
                        [
                            'user_id'        => $currentUser->id,
                            'signature_path' => $fileName,
                            'signed_at'      => now(),
                        ]
                    );
                }
            });

            // Fresh load relasi agar data lengkap untuk WhatsApp & Email
            $booking->load(['user', 'vehicle', 'driver', 'log']);

            // ------------------------------------------------------------------
            // 1. NOTIFIKASI WHATSAPP (SISTEM LAMA)
            // ------------------------------------------------------------------
            if (!empty($booking->user?->phone)) {
                $msg = "🎉 *BAST PENGEMBALIAN DISAHKAN*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Berita Acara Serah Terima (BAST) pengembalian kendaraan dinas (" . $booking->letter_number . ") telah disahkan oleh Pejabat Berwenang.\n\n"
                    . "Seluruh alur peminjaman telah SELESAI. Terima kasih!";

                WhatsAppService::sendMessage($booking->user->phone, $msg);
            }

            // 2. NOTIFIKASI EMAIL (PEMOHON, DRIVER, & ADMIN APP)
            try {
                $targetUsers = collect();

                // a. Pemohon
                if ($booking->user && !empty($booking->user->email)) {
                    $targetUsers->push([
                        'email' => $booking->user->email,
                        'name'  => $booking->user->name
                    ]);
                }

                // b. Driver (jika ada)
                if ($booking->driver && !empty($booking->driver->email)) {
                    $targetUsers->push([
                        'email' => $booking->driver->email,
                        'name'  => $booking->driver->name
                    ]);
                }

                // c. Admin App
                $adminAppUsers = User::whereIn('role', ['admin_app', 'admin_lap'])
                    ->whereNotNull('email')
                    ->get(['email', 'name']);

                foreach ($adminAppUsers as $admin) {
                    $targetUsers->push([
                        'email' => $admin->email,
                        'name'  => $admin->name
                    ]);
                }

                // Unique berdasarkan email agar tidak ada email ganda
                $uniqueRecipients = $targetUsers->unique('email');

                // Trigger pengiriman email personal per penerima via Queue
                foreach ($uniqueRecipients as $recipient) {
                    Mail::to($recipient['email'])->queue(
                        new BookingCompletedMail($booking, $recipient['name'])
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim email BookingCompletedMail: ' . $e->getMessage());
            }

            return redirect()->route('bookings.index')->with('success', 'BAST Pengembalian disahkan. Seluruh alur selesai dan email dokumen telah dikirim.');
        }

        return back()->withErrors(['error' => 'Status permohonan tidak valid untuk disetujui.']);
    }

    /**
     * TAHAP 3A: DRIVER/PEMOHON ISI LOG KEBERANGKATAN (approved -> trip_started)
     */
    public function startTrip(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $isAdmin  = in_array($currentUser->role, ['admin_app', 'admin_lap']);
        $isDriver = $booking->driver_id === $currentUser->id;
        $isOwner  = $booking->user_id === $currentUser->id;

        if (!$isAdmin && !$isDriver && !$isOwner) {
            abort(403, 'Akses ditolak.');
        }

        if ($booking->status !== 'approved') {
            return back()->withErrors(['error' => 'Perjalanan belum disetujui oleh Atasan atau sudah berjalan.']);
        }

        $validated = $request->validate([
            'start_fuel_level'   => ['required', 'string', 'max:20'],
            'start_photo'        => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'items'              => ['required', 'string'],
            'has_incident'       => ['nullable', 'string'],
            'notes'              => ['required_if:has_incident,true', 'nullable', 'string'],
            'condition_photos'   => ['required_if:has_incident,true', 'array', 'min:1', 'max:5'],
            'condition_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $items = json_decode($validated['items'], true);
        if (!is_array($items)) {
            return back()->withInput()->withErrors(['items' => 'Format data checklist tidak valid.']);
        }

        if ($request->hasFile('start_photo')) {
            $validated['start_photo'] = $request->file('start_photo')->store('vehicle_logs/starts', 'public');
        }

        $uploadedConditionPhotos = [];
        if ($request->hasFile('condition_photos')) {
            foreach ($request->file('condition_photos') as $photo) {
                if ($photo->isValid()) {
                    $uploadedConditionPhotos[] = $photo->store('vehicle_logs/incidents', 'public');
                }
            }
        }

        DB::transaction(function () use ($booking, $validated, $currentUser, $items, $uploadedConditionPhotos) {
            // Ubah status ke trip_started
            $booking->update([
                'status' => 'trip_started',
            ]);

            $notesPayload = [
                'start' => $validated['notes'] ?? null,
                'end'   => null,
            ];

            $photosPayload = [
                'start' => $uploadedConditionPhotos,
                'end'   => [],
            ];

            $log = VehicleLog::create([
                'booking_id'       => $booking->id,
                'vehicle_id'       => $booking->vehicle_id,
                'checked_out_at'   => now(),
                'start_fuel_level' => $validated['start_fuel_level'],
                'start_photo'      => $validated['start_photo'] ?? null,
                'condition_notes'  => $notesPayload,
                'condition_photos' => $photosPayload,
            ]);

            TripChecklist::create([
                'booking_id'     => $booking->id,
                'vehicle_id'     => $booking->vehicle_id,
                'vehicle_log_id' => $log->id,
                'type'           => 'start',
                'items'          => $items,
                'notes'          => $validated['notes'] ?? null,
                'created_by'     => $currentUser->id,
            ]);
        });

        if ($currentUser->id !== $booking->user_id && !empty($booking->user?->phone)) 
        {
            $msgPemohon = "✍️ *PERMINTAAN TANDA TANGAN KEBERANGKATAN*\n\n"
                . "Halo *" . $booking->user->name . "*,\n"
                . "Log keberangkatan armada untuk No. Surat *" . $booking->letter_number . "* telah diisi oleh Driver (" . $currentUser->name . ").\n\n"
                . "Mohon lakukan penandatanganan pengesahan BAST Peminjaman melalui aplikasi Balmon-Fleet.";

            WhatsAppService::sendMessage($booking->user->phone, $msgPemohon);
        }

        return redirect()->route('bookings.index')->with('success', 'Data keberangkatan berhasil disimpan. Menunggu penandatanganan pemohon.');
    }

    /**
     * TAHAP 3B: PEMOHON TANDA TANGAN KEBERANGKATAN (trip_started -> on_trip)
     */
    public function signDeparture(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // KHUSUS PEMOHON ATAU ADMIN
        $isAdmin = in_array($currentUser->role, ['admin_app', 'admin_lap']);
        $isOwner = $booking->user_id === $currentUser->id;

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Hanya pemohon yang dapat menandatangani dokumen keberangkatan ini.');
        }

        if ($booking->status !== 'trip_started') {
            return back()->withErrors(['error' => 'Status perjalanan tidak valid untuk ditandatangani.']);
        }

        $request->validate([
            'signature' => ['required', 'string'],
        ], [
            'signature.required' => 'Tanda tangan pemohon wajib dibubuhkan.',
        ]);

        DB::transaction(function () use ($booking, $request, $currentUser) {
            // Dekode Base64 & Simpan Gambar PNG
            $base64Image = $request->input('signature');
            @list(, $fileData) = explode(';', $base64Image);
            @list(, $fileData) = explode(',', $fileData);
            $decodedImage = base64_decode($fileData);

            $fileName = 'signatures/sig_dept_' . $booking->id . '_' . Str::random(10) . '.png';
            Storage::disk('public')->put($fileName, $decodedImage);

            // Simpan TTD BAST Peminjaman
            BookingSignature::updateOrCreate(
                [
                    'booking_id'    => $booking->id,
                    'document_type' => 'bast_peminjaman',
                    'signer_role'   => 'pemohon',
                ],
                [
                    'user_id'        => $currentUser->id,
                    'signature_path' => $fileName,
                    'signed_at'      => now(),
                ]
            );

            // Ubah status ke on_trip
            $booking->update([
                'status' => 'on_trip',
            ]);
        });

        return redirect()->route('bookings.index')->with('success', 'Tanda tangan keberangkatan berhasil disimpan. Perjalanan dinas resmi dimulai.');
    }

    /**
     * Complete Trip: Pengisian Log Kepulangan oleh Driver/Admin (Tanpa TTD)
     */
    public function completeTrip(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // 1. Hitung apakah perjalanan multi-day (>= 1 hari)
        $startDate = \Carbon\Carbon::parse($booking->start_date);
        $endDate   = \Carbon\Carbon::parse($booking->end_date);
        $isMultiDay = $startDate->diffInDays($endDate) >= 1;

        // 2. Validasi Input (Mengikuti pola startTrip: items berupa JSON string)
        $validated = $request->validate([
            'end_fuel_level'     => ['required', 'string', 'max:20'],
            'end_photo'          => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_washed'          => [$isMultiDay ? 'accepted' : 'nullable', 'boolean'],
            'wash_receipt_photo' => [$isMultiDay ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'items'              => ['required', 'string'], // JSON String dari modal
            'has_incident'       => ['nullable', 'string'],
            'condition_notes'    => ['required_if:has_incident,true', 'nullable', 'string'],
            'condition_photos'   => ['required_if:has_incident,true', 'array', 'min:1', 'max:5'],
            'condition_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'end_fuel_level.required'     => 'Kapasitas BBM akhir wajib dipilih.',
            'end_photo.required'          => 'Foto BBM pengembalian wajib diunggah.',
            'is_washed.accepted'          => 'Kendaraan wajib dicuci terlebih dahulu untuk perjalanan multi-hari.',
            'wash_receipt_photo.required' => 'Foto nota / bukti cuci mobil wajib diunggah.',
            'condition_notes.required_if' => 'Catatan kerusakan / insiden wajib diisi jika ada kendala.',
            'condition_photos.required_if'=> 'Foto bukti kerusakan wajib diunggah jika ada kendala.',
        ]);

        // 3. Decode JSON Items
        $items = json_decode($validated['items'], true);
        if (!is_array($items)) {
            return back()->withInput()->withErrors(['items' => 'Format data checklist tidak valid.']);
        }

        $log = $booking->log;
        if (!$log) {
            return redirect()->back()->with('error', 'Log kendaraan tidak ditemukan.');
        }

        // 4. Upload File Foto BBM Akhir & Bukti Cuci
        $endPhotoPath = null;
        if ($request->hasFile('end_photo')) {
            $endPhotoPath = $request->file('end_photo')->store('vehicle_logs/ends', 'public');
        }

        $washReceiptPath = null;
        if ($request->hasFile('wash_receipt_photo')) {
            $washReceiptPath = $request->file('wash_receipt_photo')->store('wash_receipts', 'public');
        }

        // 5. Upload Foto Kerusakan/Insiden Kepulangan (jika ada)
        $uploadedEndConditionPhotos = [];
        if ($request->hasFile('condition_photos')) {
            foreach ($request->file('condition_photos') as $photo) {
                if ($photo->isValid()) {
                    $uploadedEndConditionPhotos[] = $photo->store('vehicle_logs/incidents', 'public');
                }
            }
        }

        // ------------------------------------------------------------------
        // LOGIKA BARU: CEK APAPUN CHECKLIST YANG BERNILAI FALSE ATAU ADA INSIDEN
        // ------------------------------------------------------------------
        $hasFailedChecklist = false;
        foreach ($items as $key => $value) {
            // Cek jika bernilai boolean false, integer 0, atau string 'false'/'0'
            if ($value === false || $value === 0 || $value === 'false' || $value === '0') {
                $hasFailedChecklist = true;
                break;
            }
        }

        // Jika ada insiden dilaporkan atau ada item checklist bernilai false
        $isVehicleDamaged = $hasFailedChecklist || filter_var($request->input('has_incident'), FILTER_VALIDATE_BOOLEAN);

        // 6. Jalankan DB Transaction
        DB::transaction(function () use ($request, $booking, $log, $validated, $currentUser, $items, $endPhotoPath, $washReceiptPath, $uploadedEndConditionPhotos, $isVehicleDamaged) {
            
            // A. Update payload condition_notes & condition_photos pada VehicleLog
            $notesPayload = is_array($log->condition_notes) 
                ? $log->condition_notes 
                : json_decode($log->condition_notes ?? '{}', true);
            $notesPayload['end'] = $validated['condition_notes'] ?? null;

            $photosPayload = is_array($log->condition_photos) 
                ? $log->condition_photos 
                : json_decode($log->condition_photos ?? '{}', true);
            $photosPayload['end'] = $uploadedEndConditionPhotos;

            $log->update([
                'checked_in_at'    => now(),
                'end_fuel_level'   => $validated['end_fuel_level'],
                'end_photo'        => $endPhotoPath ?? $log->end_photo,
                'condition_notes'  => $notesPayload,
                'condition_photos' => $photosPayload,
            ]);

            // B. Simpan / Update TripChecklist Kepulangan (type = 'end')
            TripChecklist::updateOrCreate([
                'booking_id'     => $booking->id,
                'vehicle_log_id' => $log->id,
                'type'           => 'end',
            ], [
                'vehicle_id'         => $booking->vehicle_id,
                'user_id'            => $currentUser->id,
                'is_washed'          => $request->boolean('is_washed'),
                'wash_receipt_photo' => $washReceiptPath ?? null,
                'items'              => $items,
                'notes'              => $validated['condition_notes'] ?? null,
                'created_by'         => $currentUser->id,
            ]);

            // C. Update Status Booking ke 'trip_completed'
            $booking->update(['status' => 'trip_completed']);

            // D. UPDATE STATUS KENDARAAN KE 'maintenance' JIKA ADA CHECKLIST FALSE / INSIDEN
            if ($isVehicleDamaged && $booking->vehicle) {
                $booking->vehicle->update([
                    'status' => 'maintenance'
                ]);
            }
        });

        // 7. Kirim Notifikasi WA ke Pemohon (Sesuaikan pesan jika kendaraan butuh maintenance)
        if (!empty($booking->user?->phone)) {
            $waMessage = "🏁 *LOG KEPULANGAN DIISI*\n\n"
                . "Halo *" . $booking->user->name . "*,\n"
                . "Driver/Petugas telah selesai mengisi log pengembalian untuk kendaraan *" . ($booking->vehicle?->brand ?? '') . " " . ($booking->vehicle?->model ?? '') . "*.\n\n";

            if ($isVehicleDamaged) {
                $waMessage .= "⚠️ *Catatan:* Terdapat item checklist/kondisi yang butuh perbaikan. Status kendaraan otomatis dialihkan ke *MAINTENANCE*.\n\n";
            }

            $waMessage .= "Silakan masuk ke aplikasi Balmon-Fleet untuk memeriksa hasil pemeriksaan fisik dan membubuhkan **Tanda Tangan Pengembalian Armada**.";

            try {
                WhatsAppService::sendMessage($booking->user->phone, $waMessage);
            } catch (\Exception $e) {
                Log::error("Gagal mengirim WA ke Pemohon: " . $e->getMessage());
            }
        }

        $successMessage = 'Log kepulangan berhasil disimpan. Menunggu TTD pengembalian dari pemohon.';
        if ($isVehicleDamaged) {
            $successMessage .= ' Status kendaraan otomatis diubah menjadi Maintenance karena terdapat kendala fisik/kerusakan.';
        }

        return redirect()->back()->with('success', $successMessage);
    }

    /**
     * Sign Return: Pengesahan & TTD Pengembalian oleh Pemohon
     */
    public function signReturn(Request $request, Booking $booking)
    {
        // Cek apakah jenis surat adalah Nota Dinas
        $isNotaDinas = $booking->letter_type === 'nota_dinas';

        // 1. Validasi Input
        $request->validate([
            'signature'            => ['required', 'string'],
            'return_letter_number' => [$isNotaDinas ? 'required' : 'nullable', 'string', 'max:100'],
            'return_letter_file'   => [$isNotaDinas ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'signature.required'            => 'Tanda tangan pemohon wajib dibubuhkan.',
            'return_letter_number.required' => 'Nomor Surat Tugas wajib diisi untuk permohonan berbasis Nota Dinas.',
            'return_letter_file.required'   => 'File Surat Tugas (PDF) wajib diunggah untuk permohonan berbasis Nota Dinas.',
            'return_letter_file.mimes'      => 'File Surat Tugas harus berformat PDF.',
        ]);

        // 2. Upload File Surat Tugas Susulan (jika diunggah)
        $returnLetterPath = null;
        if ($request->hasFile('return_letter_file')) {
            // Hapus file lama di storage jika ada re-upload
            if ($booking->return_letter_file && Storage::disk('public')->exists($booking->return_letter_file)) {
                Storage::disk('public')->delete($booking->return_letter_file);
            }
            $returnLetterPath = $request->file('return_letter_file')->store('return_letters', 'public');
        }

        DB::transaction(function () use ($booking, $request, $returnLetterPath) {
            // 3. Dekode Base64 & Simpan File PNG TTD Pemohon
            $signatureData = $request->input('signature');
            @list(, $fileData) = explode(';', $signatureData);
            @list(, $fileData) = explode(',', $fileData);
            $imageBinary = base64_decode($fileData);
            
            $filename = 'signatures/return_pemohon_' . $booking->id . '_' . time() . '.png';
            Storage::disk('public')->put($filename, $imageBinary);

            // 4. Simpan Record TTD untuk 2 Jenis Dokumen (Formulir & BAST Pengembalian)
            foreach (['formulir_pengembalian', 'bast_pengembalian'] as $docType) {
                BookingSignature::updateOrCreate([
                    'booking_id'    => $booking->id,
                    'user_id'       => Auth::id(),
                    'signer_role'   => 'pemohon',
                    'document_type' => $docType,
                ], [
                    'signature_path'=> $filename,
                    'signed_at'     => now(),
                ]);
            }

            // 5. UPDATE TABEL BOOKINGS (Status, No Surat Tugas, dan Path File PDF)
            $bookingUpdateData = [
                'status' => 'returned',
            ];

            if ($request->filled('return_letter_number')) {
                $bookingUpdateData['return_letter_number'] = $request->input('return_letter_number');
            }

            if ($returnLetterPath) {
                $bookingUpdateData['return_letter_file'] = $returnLetterPath;
            }

            $booking->update($bookingUpdateData);
        });

        // 6. Kirim Notifikasi WA ke Seluruh Admin Lapangan
        $adminUsers = User::whereIn('role', ['admin_lap'])
            ->whereNotNull('phone')
            ->get();

        if ($adminUsers->isNotEmpty()) {
            $pemohonName = Auth::user()->name;
            $vehicleName = ($booking->vehicle?->brand ?? '') . ' ' . ($booking->vehicle?->model ?? '') . ' (' . ($booking->vehicle?->plate_number ?? '') . ')';

            $waMessageAdmin = "📥 *PENGEMBALIAN ARMADA*\n\n"
                . "Halo,\n"
                . "Pemohon *" . $pemohonName . "* telah selesai menandatangani **Formulir & BAST Pengembalian Armada**.\n\n"
                . "📋 *Rincian Peminjaman:*\n"
                . "• No Surat: *" . $booking->letter_number . "*\n"
                . "• Unit Armada: *" . $vehicleName . "*\n"
                . "• Tujuan: *" . $booking->destination . "*\n"
                . "• Tanggal Mulai: *" . $booking->start_date->format('d M Y H:i') . "*\n"
                . "• Tanggal Selesai: *" . $booking->end_date->format('d M Y H:i') . "*\n"
                . "Silakan lakukan peninjauan fisik dan verifikasi pengembalian armada di aplikasi Balmon-Fleet.";

            foreach ($adminUsers as $admin) {
                try {
                    WhatsAppService::sendMessage($admin->phone, $waMessageAdmin);
                } catch (\Exception $e) {
                    Log::error("Gagal mengirim WA ke Admin: " . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', 'Pengembalian kendaraan dan dokumen berhasil disahkan.');
    }

    /**
     * TAHAP 5: ADMIN LAPANGAN VERIFIKASI FISIK KEPULANGAN (returned -> checked)
     */
    public function verifyReturn(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Akses ditolak. Hanya Admin Lapangan yang dapat meninjau kepulangan.');
        }

        if ($booking->status !== 'returned') {
            return back()->withErrors(['error' => 'Status permohonan harus "returned" untuk ditinjau.']);
        }

        // Cek ketersediaan TTD lama Admin Lapangan untuk BAST Pengembalian
        $existingSignature = BookingSignature::where('booking_id', $booking->id)
            ->where('document_type', 'bast_pengembalian')
            ->where('signer_role', 'admin_lap')
            ->first();

        // Validasi penyesuaian checklist dinamis & status cuci mobil oleh Admin Lapangan
        $validated = $request->validate([
            'signature'                => ['nullable', 'string'],
            'items'                    => ['required', 'string'], // Menerima JSON String items dari form admin
            'is_washed'                => ['nullable', 'boolean'],
            'admin_verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Decode items JSON
        $items = json_decode($validated['items'], true);
        if (!is_array($items)) {
            return back()->withInput()->withErrors(['items' => 'Format data checklist tidak valid.']);
        }

        // Jika TTD belum pernah ada dan input signature di form kosong
        if (!$existingSignature && !$request->filled('signature')) {
            throw ValidationException::withMessages([
                'signature' => 'Tanda tangan verifikasi Admin Lapangan wajib dibubuhkan.'
            ]);
        }

        // ------------------------------------------------------------------
        // LOGIKA BARU: EVALUASI CHECKLIST FALSE & HAS INCIDENT LOG
        // ------------------------------------------------------------------
        $hasFailedChecklist = false;
        foreach ($items as $key => $value) {
            if ($value === false || $value === 0 || $value === 'false' || $value === '0') {
                $hasFailedChecklist = true;
                break;
            }
        }

        // Cek juga insiden dari VehicleLog jika ada
        $hasLogIncident = false;
        if ($booking->log && !empty($booking->log->condition_notes)) {
            $logNotes = is_array($booking->log->condition_notes) 
                ? $booking->log->condition_notes 
                : json_decode($booking->log->condition_notes, true);
            
            if (!empty($logNotes['end'])) {
                $hasLogIncident = true;
            }
        }

        // Kendaraan dikategorikan butuh maintenance jika ada checklist false ATAU ada catatan insiden
        $isVehicleDamaged = $hasFailedChecklist || $hasLogIncident;

        DB::transaction(function () use ($booking, $currentUser, $request, $validated, $existingSignature, $items, $isVehicleDamaged) {
            // 1. Simpan / Update TTD jika ada input signature baru
            if ($request->filled('signature')) {
                if ($existingSignature && $existingSignature->signature_path) {
                    if (Storage::disk('public')->exists($existingSignature->signature_path)) {
                        Storage::disk('public')->delete($existingSignature->signature_path);
                    }
                }

                $base64Image = $request->input('signature');
                @list(, $fileData) = explode(';', $base64Image);
                @list(, $fileData) = explode(',', $fileData);
                $decodedImage = base64_decode($fileData);

                $fileName = 'signatures/sig_admin_lap_return_' . $booking->id . '_' . Str::random(8) . '.png';
                Storage::disk('public')->put($fileName, $decodedImage);

                BookingSignature::updateOrCreate(
                    [
                        'booking_id'    => $booking->id,
                        'document_type' => 'bast_pengembalian',
                        'signer_role'   => 'admin_lap',
                    ],
                    [
                        'user_id'        => $currentUser->id,
                        'signature_path' => $fileName,
                        'signed_at'      => now(),
                    ]
                );
            }

            // 2. Update status booking ke CHECKED
            $booking->update([
                'status'              => 'checked',
                'returned_checked_by' => $currentUser->id,
                'returned_checked_at' => now(),
            ]);

            // 3. Update / Create Checklist Kepulangan Snapshot JSON
            $endChecklist = $booking->checklists()->where('type', 'end')->first();

            $checklistData = [
                'items'     => $items,
                'is_washed' => $request->boolean('is_washed'),
            ];

            if ($endChecklist) {
                $endChecklist->update($checklistData);
            } else {
                TripChecklist::create(array_merge($checklistData, [
                    'booking_id'     => $booking->id,
                    'vehicle_id'     => $booking->vehicle_id,
                    'vehicle_log_id' => $booking->log?->id,
                    'type'           => 'end',
                    'created_by'     => $currentUser->id,
                ]));
            }

            // 4. Update Vehicle Log Verifikasi Admin
            if ($booking->log) {
                $booking->log->update([
                    'is_verified_by_admin'     => true,
                    'admin_verification_notes' => $validated['admin_verification_notes'] ?? null,
                ]);
            }

            // 5. PERBAIKAN LOGIKA STATUS VEHICLE:
            // Jika terbukti ada kendala/false -> status 'maintenance'
            // Jika fisik aman/lulus verifikasi -> kembalikan status ke 'available'
            if ($booking->vehicle) {
                $booking->vehicle->update([
                    'status' => $isVehicleDamaged ? 'maintenance' : 'available'
                ]);
            }
        });

        // Notifikasi WA ke Approver
        $approverPhones = User::where('role', 'approver')->pluck('phone')->filter()->toArray();
        if (!empty($approverPhones)) {
            $msgApprover = "🏁 *PEMERIKSAAN PENGEMBALIAN ARMADA SELESAI*\n\n"
                . "No. Surat: *" . $booking->letter_number . "*\n"
                . "Pemohon: *" . ($booking->user->name ?? '-') . "*\n\n"
                . "Pemeriksaan fisik oleh Koordinator telah SELESAI.\n"
                . "Status Fisik Kendaraan: *" . ($isVehicleDamaged ? 'BUTUH MAINTENANCE / KENDALA' : 'LULUS (AVAILABLE)') . "*\n\n"
                . "Mohon periksa BAST Pengembalian dan berikan pengesahan akhir.";

            try {
                WhatsAppService::sendBulkMessage($approverPhones, $msgApprover);
            } catch (\Exception $e) {
                Log::error("Gagal mengirim WA ke Approver: " . $e->getMessage());
            }
        }

        $successMsg = 'Hasil verifikasi fisik dan tanda tangan berhasil disesuaikan.';
        if ($isVehicleDamaged) {
            $successMsg .= ' Status kendaraan diset ke Maintenance karena terdapat checklist false/kendala fisik.';
        } else {
            $successMsg .= ' Kendaraan telah terverifikasi aman dan status dialihkan ke Available.';
        }

        return redirect()->route('bookings.index')->with('success', $successMsg);
    }

    public function edit(Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!in_array($booking->status, ['pending', 'rejected'])) {
            return redirect()->route('bookings.index')
                ->withErrors(['error' => 'Permohonan ini sudah diproses dan tidak dapat diubah lagi.']);
        }

        $isAdmin = in_array($currentUser->role, ['admin_app', 'admin_lap']);
        $isOwner = $booking->user_id === $currentUser->id;

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah permohonan ini.');
        }

        $vehicles = Vehicle::where('status', '!=', 'maintenance')->where('type', 'monitoring')->get();
        $users = User::whereIn('role', ['admin_lap', 'pegawai', 'driver'])->orderBy('name', 'asc')->get(['id', 'name', 'nip', 'jabatan', 'role']);

        return view('bookings.edit', compact('booking', 'vehicles', 'users'));
    }

    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!in_array($booking->status, ['pending', 'rejected'])) {
            return redirect()->route('bookings.index')
                ->withErrors(['error' => 'Permohonan yang sudah diproses atau disetujui tidak dapat diubah lagi.']);
        }

        $isAdmin = in_array($currentUser->role, ['admin_app', 'admin_lap']);
        $isOwner = $booking->user_id === $currentUser->id;

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah permohonan ini.');
        }

        $validated = $request->validated();

        // Perbarui jenis dokumen (surat_tugas / nota_dinas)
        $validated['letter_type'] = $request->input('letter_type', $booking->letter_type); // <-- BARU

        $validated['participants'] = isset($validated['participants']) 
            ? array_values(array_filter($validated['participants'])) 
            : [];

        if ($validated['vehicle_type'] === 'operational') {
            if ($booking->draft_booking_id && $booking->draftBooking) {
                $validated['vehicle_id'] = $booking->draftBooking->vehicle_id;
            } else {
                $validated['vehicle_id'] = null;
            }
        }

        if (!empty($validated['vehicle_id'])) {
            $isVehicleOverlapped = Booking::where('vehicle_id', $validated['vehicle_id'])
                ->where('id', '!=', $booking->id)
                ->whereIn('status', ['waiting', 'approved', 'on_trip', 'returned', 'checked'])
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

        $allPersonnelIds = array_merge([$booking->user_id], $validated['participants']);

        $userOverlap = Booking::where('id', '!=', $booking->id)
            ->whereIn('status', ['waiting', 'approved', 'on_trip', 'returned', 'checked'])
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

        if ($request->hasFile('letter_file')) {
            if ($booking->letter_file && Storage::disk('public')->exists($booking->letter_file)) {
                Storage::disk('public')->delete($booking->letter_file);
            }
            $validated['letter_file'] = $request->file('letter_file')->store('letters', 'public');
        }

        if ($booking->letter_number !== $validated['letter_number']) {
            $validated['letter_slug'] = $this->generateUniqueSlug($validated['letter_number'], $booking->id);
        }

        if ($booking->status === 'rejected') {
            $validated['status'] = 'pending';
        }

        $booking->update($validated);

        return redirect()->route('bookings.index')->with('success', 'Permohonan peminjaman berhasil diperbarui.');
    }

    public function cancelBooking(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($booking->user_id !== $currentUser->id && !in_array($currentUser->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk membatalkan pengajuan ini.');
        }

        if ($booking->status !== 'pending') {
            return back()->withErrors(['error' => 'Permohonan yang sudah diproses tidak dapat dibatalkan langsung oleh pemohon.']);
        }

        $booking->update([
            'status' => 'canceled',
        ]);

        if (!empty($booking->user?->phone)) {
            $msg = "🚫 *PEMBATALAN PENGAJUAN PEMINJAMAN*\n\n"
                . "Halo *" . $booking->user->name . "*,\n"
                . "Pengajuan peminjaman armada Anda dengan nomor surat *" . $booking->letter_number . "* telah dibatalkan.";

            WhatsAppService::sendMessage($booking->user->phone, $msg);
        }

        return back()->with('success', 'Pengajuan peminjaman berhasil dibatalkan.');
    }

    public function cancelApproval(Request $request, Booking $booking)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!in_array($currentUser->role, ['admin_app', 'admin_lap', 'approver'])) {
            abort(403, 'Akses ditolak.');
        }

        if (!in_array($booking->status, ['waiting', 'approved'])) {
            return back()->withErrors(['error' => 'Hanya permohonan berstatus "waiting" atau "approved" yang dapat dibatalkan.']);
        }

        $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ], [
            'cancel_reason.required' => 'Alasan pembatalan persetujuan wajib diisi.',
        ]);

        $booking->load(['user', 'vehicle', 'driver']);

        $driverUser = $booking->driver;

        $booking->update([
            'status'     => 'canceled',
            'vehicle_id' => null,
            'driver_id'  => null,
            'admin_note' => 'BATAL PERSETUJUAN: ' . $request->cancel_reason,
        ]);

        if (!empty($booking->user?->phone)) {
            $msgApplicant = "⚠️ *PERSETUJUAN PEMINJAMAN DIBATALKAN*\n\n"
                . "Halo *" . $booking->user->name . "*,\n"
                . "Persetujuan peminjaman armada dengan nomor surat *" . $booking->letter_number . "* telah dibatalkan.\n\n"
                . "📝 *Alasan Pembatalan:* " . $request->cancel_reason;

            WhatsAppService::sendMessage($booking->user->phone, $msgApplicant);
        }

        if ($driverUser && !empty($driverUser->phone)) {
            $msgDriver = "ℹ️ *PEMBATALAN TUGAS DRIVER*\n\n"
                . "Halo *" . $driverUser->name . "*,\n"
                . "Penugasan driver untuk perjalanan dinas ke *" . $booking->destination . "* (" . $booking->letter_number . ") telah dibatalkan.";

            WhatsAppService::sendMessage($driverUser->phone, $msgDriver);
        }

        return back()->with('success', 'Persetujuan peminjaman berhasil dibatalkan dan alokasi armada/driver telah dilepaskan.');
    }
}