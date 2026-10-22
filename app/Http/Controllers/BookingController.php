<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Mail\BookingSubmittedMail;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use App\Services\WhatsAppService;
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
        // Admin App, Admin Lapangan, dan Approver melihat SELURUH booking.
        // Pegawai biasa dan Driver HANYA melihat yang bersangkutan dengan dirinya.
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

        // 2. Data Master Driver & Pegawai Non-Driver
        $primaryDrivers = User::where('role', 'driver')->orderBy('name', 'asc')->get();
        $nonDrivers     = User::where('role', '!=', 'driver')->orderBy('name', 'asc')->get();

        // Gabungkan Master Driver dan Seluruh Pegawai
        $availableDrivers = $primaryDrivers->concat($nonDrivers);

        // 3. Data Master Kendaraan Non-Maintenance
        $availableVehicles = Vehicle::where('status', '!=', 'maintenance')
            ->orderBy('code', 'asc')
            ->get();

        // 4. Dataset Jadwal Booking Aktif (Untuk Filter Realtime Alpine.js)
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

        // 5. Kalkulasi Odometer & Flag Reassign
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
            'activeBookings'
        ));
    }

    public function create()
    {
        $users = User::whereIn('role', ['admin_lap', 'pegawai', 'driver'])
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

    public function parseDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:5120',
        ]);

        try {
            $file = $request->file('document');
            $filePath = $file->store('letters', 'public');

            $pdfBase64 = base64_encode(file_get_contents($file->getPathname()));

            $prompt = "Kamu adalah sistem ekstraktor dokumen resmi. Analisis dokumen Surat Tugas dalam berkas PDF ini dan ekstrak datanya ke dalam format JSON murni.

            Data yang harus diekstrak:
            1. letter_number: Nomor surat tugas lengkap.
            2. destination: Tempat/Lokasi pelaksanaan.
            3. purpose: Maksud/keperluan kegiatan.
            4. start_date: Tanggal mulai pelaksanaan (format YYYY-MM-DD).
            5. end_date: Tanggal selesai pelaksanaan (format YYYY-MM-DD).
            6. nips: Array berisi daftar NIP (18 digit angka) dari pegawai yang ditugaskan.";

            $apiKey = config('services.gemini.api_key');
            $model  = config('services.gemini.model', 'gemini-flash-latest');

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
        $validated = $request->validated();

        if ($request->hasFile('letter_file')) {
            $validated['letter_file'] = $request->file('letter_file')->store('letters', 'public');
        } elseif ($request->filled('letter_file_temp')) {
            $validated['letter_file'] = $request->input('letter_file_temp');
        }

        if (isset($validated['participants']) && is_array($validated['participants'])) {
            $validated['participants'] = array_values(array_filter($validated['participants']));
        } else {
            $validated['participants'] = [];
        }

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
                        'vehicle_id' => 'Mobil yang dipilih sudah memiliki jadwal peminjaman yang disetujui pada rentang tanggal tersebut.'
                    ]);
            }
        }

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
                    'participants' => 'Anda atau salah satu anggota tim yang dipilih sudah memiliki jadwal perjalanan dinas lain pada rentang waktu ini.'
                ]);
        }

        $validated['letter_slug'] = $this->generateUniqueSlug($validated['letter_number']);
        $validated['user_id']     = Auth::id();
        $validated['status']      = 'pending';

        $booking = Booking::create($validated);

        // Notifikasi WA ke Admin Lapangan
        $adminPhones = User::where('role', 'admin_lap')->pluck('phone')->filter()->toArray();
        if (!empty($adminPhones)) {
            $msgAdmin = "📩 *PERMOHONAN PEMINJAMAN BARU*\n\n"
                . "No. Surat: {$booking->letter_number}\n"
                . "Pemohon: " . Auth::user()->name . "\n"
                . "Tipe Armada: " . strtoupper($booking->vehicle_type) . "\n"
                . "Tujuan: {$booking->destination}\n"
                . "Jadwal: " . $booking->start_date->format('d/m/Y H:i') . " s.d. " . $booking->end_date->format('d/m/Y H:i') . "\n\n"
                . "Mohon lakukan alokasi armada dan driver via dashboard.";

            WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);
        }

        // Notifikasi WA ke Pemohon Utama
        if (Auth::user()->phone) {
            $msgApplicant = "✅ *PENGAJUAN PEMINJAMAN BERHASIL*\n\n"
                . "No. Surat: {$booking->letter_number}\n"
                . "Tujuan: {$booking->destination}\n"
                . "Jadwal: " . $booking->start_date->format('d/m/Y H:i') . " s.d. " . $booking->end_date->format('d/m/Y H:i') . "\n\n"
                . "Permohonan Anda sedang dalam proses alokasi oleh Admin Lapangan.";

            WhatsAppService::sendMessage(Auth::user()->phone, $msgApplicant);
        }

        // try {
        //     Mail::to($booking->user->email ?? Auth::user()->email)->send(new BookingSubmittedMail($booking));
        // } catch (\Exception $e) {
        //     Log::error('Gagal mengirim email permohonan: ' . $e->getMessage());
        // }

        return redirect()->route('bookings.index')
            ->with('success', 'Pengajuan permohonan peminjaman armada berhasil dikirim.');
    }

    public function show(Booking $booking)
    {
        $booking->load(['user', 'vehicle', 'driver', 'log', 'allocator', 'approver', 'returnChecker', 'returnApprover']);

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
        ], [
            'vehicle_id.required_if' => 'Unit armada wajib dipilih saat melakukan alokasi.',
            'admin_note.required_if' => 'Alasan penolakan wajib diisi.',
        ]);

        if ($request->action === 'approve') {
            $startDate = $booking->start_date;
            $endDate   = $booking->end_date;

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

            // Update status ke WAITING & simpan audit trail allocated_by
            $booking->update([
                'status'       => 'waiting',
                'vehicle_id'   => $request->vehicle_id,
                'driver_id'    => $request->driver_id,
                'admin_note'   => $request->admin_note,
                'allocated_by' => $currentUser->id,
                'allocated_at' => now(),
            ]);

            $booking->load(['user', 'vehicle', 'driver']);

            $vehicleName = $booking->vehicle 
                ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')' 
                : '-';
                
            $driverName = $booking->driver ? $booking->driver->name : 'Lepas Kunci (Tanpa Driver)';

            // Notifikasi ke Approver
            $approverPhones = User::where('role', 'approver')->pluck('phone')->filter()->toArray();
            if (!empty($approverPhones)) {
                $msgApprover = "📋 *PERMOHONAN MENUNGGU PERSETUJUAN (ALOKASI SELESAI)*\n\n"
                    . "No. Surat: *" . $booking->letter_number . "*\n"
                    . "Pemohon: *" . ($booking->user->name ?? '-') . "*\n"
                    . "Armada: *" . $vehicleName . "*\n"
                    . "Driver: *" . $driverName . "*\n\n"
                    . "Mohon periksa dan berikan persetujuan pada sistem Balmon Fleet.";

                WhatsAppService::sendBulkMessage($approverPhones, $msgApprover);
            }

            return back()->with('success', 'Armada & Driver berhasil dialokasikan. Permohonan kini menunggu persetujuan Atasan/Approver.');

        } else {
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
        ]);

        $action = $validated['action'];
        $note   = $validated['admin_note'] ?? null;

        if ($action === 'reject') {
            $booking->update([
                'status'     => 'rejected',
                'admin_note' => $note,
            ]);

            if (!empty($booking->user?->phone)) {
                $msg = "❌ *PERMOHONAN PEMINJAMAN DITOLAK OLEH ATASAN*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Permohonan dengan No. Surat *" . $booking->letter_number . "* telah ditolak oleh Pejabat Berwenang.\n"
                    . "📝 *Alasan:* " . ($note ?? '-');

                WhatsAppService::sendMessage($booking->user->phone, $msg);
            }

            return redirect()->route('bookings.index')->with('success', 'Permohonan telah ditolak.');
        }

        // TAHAP 2: PERSETUJUAN KEBERANGKATAN (waiting -> approved)
        if ($booking->status === 'waiting') {
            $booking->update([
                'status'      => 'approved',
                'approved_by' => $currentUser->id,
                'approved_at' => now(),
                'admin_note'  => $note ?? $booking->admin_note,
            ]);

            $booking->load(['user', 'vehicle', 'driver']);

            $vehicleName = $booking->vehicle 
                ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')' 
                : '-';

            if (!empty($booking->user?->phone)) {
                $msgApplicant = "✅ *PERMOHONAN PEMINJAMAN DISETUJUI*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Permohonan armada Anda (" . $booking->letter_number . ") telah *DISETUJU/APPROVED* oleh Pejabat Berwenang.\n\n"
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
            $booking->update([
                'status'               => 'completed',
                'returned_approved_by' => $currentUser->id,
                'returned_approved_at' => now(),
                'admin_note'           => $note ?? $booking->admin_note,
            ]);

            $booking->load(['user', 'vehicle', 'log']);

            if (!empty($booking->user?->phone)) {
                $msg = "🎉 *BAST PENGEMBALIAN DISAHKAN*\n\n"
                    . "Halo *" . $booking->user->name . "*,\n"
                    . "Berita Acara Serah Terima (BAST) pengembalian kendaraan dinas (" . $booking->letter_number . ") telah disahkan oleh Pejabat Berwenang.\n\n"
                    . "Seluruh alur peminjaman telah SELESAI. Terima kasih!";

                WhatsAppService::sendMessage($booking->user->phone, $msg);
            }

            return redirect()->route('bookings.index')->with('success', 'BAST Pengembalian disahkan. Seluruh alur selesai.');
        }

        return back()->withErrors(['error' => 'Status permohonan tidak valid untuk disetujui.']);
    }

    /**
     * TAHAP 3: KEBERANGKATAN KENDARAAN (approved -> on_trip)
     */
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
            return back()->withErrors(['error' => 'Perjalanan belum disetujui oleh Atasan atau sudah berjalan.']);
        }

        $validated = $request->validate([
            'start_km'         => ['required', 'numeric', 'min:0'],
            'start_fuel_level' => ['required', 'string', 'max:20'],
            'start_photo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($request->hasFile('start_photo')) {
            $validated['start_photo'] = $request->file('start_photo')->store('vehicle_logs/starts', 'public');
        }

        DB::transaction(function () use ($booking, $validated) {
            $booking->update([
                'status' => 'on_trip',
            ]);

            VehicleLog::create([
                'booking_id'       => $booking->id,
                'vehicle_id'       => $booking->vehicle_id,
                'checked_out_at'   => now(),
                'start_km'         => $validated['start_km'],
                'start_fuel_level' => $validated['start_fuel_level'],
                'start_photo'      => $validated['start_photo'] ?? null,
            ]);
        });

        $booking->load(['user', 'vehicle', 'driver']);

        $vehicleName = $booking->vehicle 
            ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->plate_number . ')' 
            : 'Armada Operasional';

        $recipientUserIds = collect([$booking->user_id]);
        if (!empty($booking->participants)) {
            $recipientUserIds = $recipientUserIds->merge($booking->participants);
        }
        $adminIds = User::where('role', 'admin_lap')->pluck('id');
        $recipientUserIds = $recipientUserIds->merge($adminIds)->unique();

        $recipients = User::whereIn('id', $recipientUserIds)->whereNotNull('phone')->where('phone', '!=', '')->get();

        foreach ($recipients as $recipient) {
            $msgDeparture = "🚗 *PERJALANAN DINAS DIMULAI*\n\n"
                . "Halo *" . $recipient->name . "*,\n\n"
                . "No. Surat: *" . $booking->letter_number . "*\n"
                . "Pemohon: *" . ($booking->user->name ?? '-') . "*\n"
                . "Tujuan: *" . $booking->destination . "*\n\n"
                . "🚘 *Armada:* " . $vehicleName . "\n"
                . "📍 *KM Awal:* " . number_format($validated['start_km'], 0, ',', '.') . " KM\n"
                . "⛽ *BBM Awal:* " . $validated['start_fuel_level'] . "\n\n"
                . "Semoga perjalanan dinas berjalan lancar dan utamakan keselamatan dalam berkendara.";

            try {
                WhatsAppService::sendMessage($recipient->phone, $msgDeparture);
            } catch (\Exception $e) {
                Log::error("Gagal mengirim WA Keberangkatan ke {$recipient->name}: " . $e->getMessage());
            }
        }

        return redirect()->route('bookings.index')->with('success', 'Perjalanan dinas berhasil dimulai.');
    }

    /**
     * TAHAP 4: DRIVER INPUT DATA KEPULANGAN (on_trip -> returned)
     */
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

        $log = $booking->log;
        if (!$log) {
            return back()->withErrors(['error' => 'Data log keberangkatan kendaraan tidak ditemukan.']);
        }

        $validated = $request->validate([
            'end_km'             => ['required', 'numeric', 'gte:' . $log->start_km],
            'end_fuel_level'     => ['required', 'string', 'max:20'],
            'end_photo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'condition_notes'    => ['nullable', 'string'],
            'condition_photos'   => ['nullable', 'array'],
            'condition_photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $distanceTraveled = $validated['end_km'] - $log->start_km;

        if ($request->hasFile('end_photo')) {
            $validated['end_photo'] = $request->file('end_photo')->store('vehicle_logs/ends', 'public');
        }

        $uploadedConditionPhotos = [];
        if ($request->hasFile('condition_photos')) {
            foreach ($request->file('condition_photos') as $photo) {
                $uploadedConditionPhotos[] = $photo->store('vehicle_logs/incidents', 'public');
            }
        }

        DB::transaction(function () use ($booking, $log, $validated, $distanceTraveled, $uploadedConditionPhotos) {
            $log->update([
                'checked_in_at'     => now(),
                'end_km'            => $validated['end_km'],
                'end_fuel_level'    => $validated['end_fuel_level'],
                'end_photo'         => $validated['end_photo'] ?? null,
                'distance_traveled' => $distanceTraveled,
                'condition_notes'   => $validated['condition_notes'] ?? null,
                'condition_photos'  => !empty($uploadedConditionPhotos) ? json_encode($uploadedConditionPhotos) : null,
            ]);

            // Update status ke RETURNED (Menunggu Peninjauan Admin Lapangan)
            $booking->update([
                'status' => 'returned',
            ]);
        });

        // Notifikasi WA ke Admin Lapangan untuk peninjauan fisik kendaraan
        $adminPhones = User::where('role', 'admin_lap')->pluck('phone')->filter()->toArray();
        if (!empty($adminPhones)) {
            $msgAdmin = "🚗 *KENDARAAN DINAS KEMBALI*\n\n"
                . "No. Surat: *" . $booking->letter_number . "*\n"
                . "Pemohon: *" . ($booking->user->name ?? '-') . "*\n"
                . "Total Jarak Tempuh: *" . number_format($distanceTraveled, 0, ',', '.') . " KM*\n\n"
                . "Mohon periksa fisik kendaraan dan lakukan konfirmasi peninjauan di sistem.";

            WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);
        }

        return redirect()->route('bookings.index')->with('success', 'Data kepulangan kendaraan berhasil dimasukkan. Menunggu pemeriksaan fisik oleh Admin Lapangan.');
    }

    /**
     * TAHAP 5: ADMIN LAPANGAN MENINJAU KEPULANGAN (returned -> checked)
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

        $booking->update([
            'status'              => 'checked',
            'returned_checked_by' => $currentUser->id,
            'returned_checked_at' => now(),
        ]);

        // Notifikasi WA ke Approver untuk pengesahan BAST Pengembalian
        $approverPhones = User::where('role', 'approver')->pluck('phone')->filter()->toArray();
        if (!empty($approverPhones)) {
            $msgApprover = "🏁 *PEMERIKSAAN PENGEMBALIAN ARMADA SELESAI*\n\n"
                . "No. Surat: *" . $booking->letter_number . "*\n"
                . "Pemohon: *" . ($booking->user->name ?? '-') . "*\n\n"
                . "Pemeriksaan fisik oleh Admin Lapangan telah SELESAI. Mohon periksa BAST Pengembalian dan berikan pengesahan akhir.";

            WhatsAppService::sendBulkMessage($approverPhones, $msgApprover);
        }

        return redirect()->route('bookings.index')->with('success', 'Pemeriksaan fisik pengembalian kendaraan disetujui. Menunggu pengesahan BAST oleh Atasan/Approver.');
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
        $users    = User::where('id', '!=', $booking->user_id)->get();

        return view('bookings.edit', compact('booking', 'vehicles', 'users'));
    }

    public function update(Request $request, Booking $booking)
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

        $validated = $request->validate([
            'letter_number' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($booking) {
                    $exists = Booking::where('id', '!=', $booking->id)
                        ->where('letter_number', $value)
                        ->where('status', '!=', 'canceled')
                        ->exists();

                    if ($exists) {
                        $fail('Nomor Surat Tugas ini sudah terdaftar pada peminjaman lain yang aktif/disetujui.');
                    }
                },
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

        $validated['participants'] = isset($validated['participants']) 
            ? array_values(array_filter($validated['participants'])) 
            : [];

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

        if ($validated['vehicle_type'] === 'operational') {
            $validated['vehicle_id'] = null;
        }

        if ($booking->letter_number !== $validated['letter_number']) {
            $validated['letter_slug'] = $this->generateUniqueSlug($validated['letter_number'], $booking->id);
        }

        if ($booking->status === 'rejected') {
            $validated['status'] = 'pending';
            $adminPhones = User::where('role', 'admin_lap')->pluck('phone')->filter()->toArray();

            if (!empty($adminPhones)) {
                $startDate = \Carbon\Carbon::parse($validated['start_date'])->format('d/m/Y H:i');
                $endDate = \Carbon\Carbon::parse($validated['end_date'])->format('d/m/Y H:i');
                $applicantName = $booking->user->name ?? Auth::user()->name;

                $msgAdmin = "📩 *PERMOHONAN PEMINJAMAN DIPERBARUI*\n\n"
                    . "No. Surat: {$validated['letter_number']}\n"
                    . "Pemohon: {$applicantName}\n"
                    . "Tipe Armada: " . strtoupper($validated['vehicle_type']) . "\n"
                    . "Tujuan: {$validated['destination']}\n"
                    . "Jadwal: {$startDate} s.d. {$endDate}\n\n"
                    . "Mohon lakukan alokasi armada dan driver via dashboard.";

                WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);
            }
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