<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DraftBooking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DraftBookingController extends Controller
{
    /**
     * Daftar Draft Request (Layer 1)
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $drafts = DraftBooking::with(['user', 'vehicle', 'driver', 'reviewer'])
            ->when(!in_array($user->role, ['admin_app', 'admin_lap', 'approver']), function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereJsonContains('participants', (string) $user->id)
                    ->orWhereJsonContains('participants', (int) $user->id);
            })
            ->latest()
            ->paginate(10);

        $availableVehicles = Vehicle::where('status', '!=', 'maintenance')->orderBy('code', 'asc')->get();
        $primaryDrivers    = User::where('role', 'driver')->orderBy('name', 'asc')->get();
        $nonDrivers        = User::where('role', '!=', 'driver')->orderBy('name', 'asc')->get();
        $availableDrivers  = $primaryDrivers->concat($nonDrivers);

        return view('draft_bookings.index', compact('drafts', 'availableVehicles', 'availableDrivers'));
    }

    /**
     * Form Pengajuan Draft (Tanpa Surat Tugas)
     */
    public function create()
    {
        // Mengambil semua armada aktif (Operasional & Monitoring)
        $vehicles = Vehicle::where('status', '!=', 'maintenance')
            ->orderBy('type', 'asc')
            ->orderBy('brand', 'asc')
            ->get();

        // Mengambil daftar driver & personel pendukung
        $drivers = User::where('role', 'driver')
            ->orderBy('name', 'asc')
            ->get();

        return view('draft_bookings.create', compact('vehicles', 'drivers'));
    }

    /**
     * Simpan Permohonan Draft Baru
     */
    public function store(Request $request)
    {
        // Validasi input beserta pesan kustom Bahasa Indonesia
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'driver_id'  => ['nullable', 'exists:users,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
        ], [
            'start_date.required'       => 'Waktu tanggal mulai peminjaman wajib dipilih.',
            'start_date.date'           => 'Format tanggal mulai tidak valid.',
            'start_date.after_or_equal' => 'Tanggal mulai peminjaman minimal hari ini.',
            
            'end_date.required'         => 'Waktu tanggal selesai peminjaman wajib dipilih.',
            'end_date.date'             => 'Format tanggal selesai tidak valid.',
            'end_date.after_or_equal'   => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            
            'vehicle_id.required'       => 'Silakan pilih unit kendaraan terlebih dahulu.',
            'vehicle_id.exists'         => 'Unit kendaraan yang dipilih tidak valid.',
            
            'driver_id.exists'          => 'Driver yang dipilih tidak terdaftar.',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

        $validated['user_id']      = Auth::id();
        $validated['vehicle_type'] = $vehicle->type;
        $validated['destination']  = $request->input('destination', 'Dalam / Luar Kota');
        $validated['purpose']      = $request->input('purpose', 'Permohonan Armada');
        $validated['status']       = 'draft';

        $draft = DraftBooking::create($validated);

        // Notifikasi WA ke Admin Lapangan
        $adminPhones = User::where('role', 'admin_lap')->pluck('phone')->filter()->toArray();
        if (!empty($adminPhones)) {
            $msgAdmin = "📝 *PERMOHONAN ARMADA BARU*\n\n"
                . "Pemohon: " . Auth::user()->name . "\n"
                . "Kendaraan: {$vehicle->brand} {$vehicle->model} ({$vehicle->plate_number})\n"
                . "Jadwal: " . date('d/m/Y', strtotime($draft->start_date)) . " s.d. " . date('d/m/Y', strtotime($draft->end_date)) . "\n\n"
                . "Mohon tinjau ketersediaan unit & driver via dashboard.";

            WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);
        }

        return redirect()->route('draft-bookings.index')
            ->with('success', 'Permohonan armada berhasil dikirim. Menunggu peninjauan Admin Lapangan.');
    }

    /**
     * Review/Plot Ketersediaan Armada oleh Admin Lapangan
     */
    public function review(Request $request, DraftBooking $draftBooking)
    {
        if (!in_array(Auth::user()->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk meninjau permohonan ini.');
        }

        $validated = $request->validate([
            'action'     => ['required', 'in:approve,reject'],
            'admin_note' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ], [
            'action.required'        => 'Aksi peninjauan harus ditentukan.',
            'admin_note.required_if' => 'Alasan penolakan wajib diisi jika permohonan ditolak.',
        ]);

        if ($validated['action'] === 'approve') {
            $draftBooking->update([
                'status'     => 'approved',
                'admin_note' => $validated['admin_note'] ?? null,
            ]);

            if ($draftBooking->user && $draftBooking->user->phone) {
                $msgPemohon = "✅ *PERMOHONAN ARMADA DISETUJUI*\n\n"
                    . "Halo {$draftBooking->user->name},\n"
                    . "Permohonan armada Anda untuk tanggal " . \Carbon\Carbon::parse($draftBooking->start_date)->locale('id')->translatedFormat('d M Y') . " telah disetujui.\n\n"
                    . "Armada: " . ($draftBooking->vehicle->brand ?? '-') . " " . ($draftBooking->vehicle->model ?? '') . "\n"
                    . "Driver: " . ($draftBooking->driver->name ?? 'Lepas Kunci') . "\n\n"
                    . "Silakan masuk ke aplikasi untuk mengunggah Surat Tugas resmi.";

                WhatsAppService::sendMessage($draftBooking->user->phone, $msgPemohon);
            }

            $message = 'Permohonan armada berhasil disetujui.';
        } else {
            $draftBooking->update([
                'status'     => 'rejected',
                'admin_note' => $validated['admin_note'],
            ]);

            if ($draftBooking->user && $draftBooking->user->phone) {
                $msgPemohon = "❌ *PERMOHONAN ARMADA DITOLAK*\n\n"
                    . "Halo {$draftBooking->user->name},\n"
                    . "Mohon maaf, permohonan armada Anda tidak dapat disetujui.\n\n"
                    . "Alasan Penolakan: {$validated['admin_note']}\n";

                WhatsAppService::sendMessage($draftBooking->user->phone, $msgPemohon);
            }

            $message = 'Permohonan armada telah ditolak.';
        }

        return redirect()->route('draft-bookings.index')->with('success', $message);
    }

    /**
     * Konversi Draft (Layer 1) menjadi Booking Resmi (Layer 2) saat Surat Tugas di-upload
     */
    public function convertToBooking(Request $request, DraftBooking $draftBooking)
    {
        if ($draftBooking->status !== 'approved') {
            return back()->withErrors(['error' => 'Draft permohonan harus disetujui terlebih dahulu oleh Admin Lapangan.']);
        }

        $request->validate([
            'letter_number' => ['required', 'string', 'max:100', 'unique:bookings,letter_number'],
            'letter_file'   => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $filePath = $request->file('letter_file')->store('letters', 'public');

        DB::transaction(function () use ($draftBooking, $request, $filePath) {
            // 1. Buat Record Baru di Tabel Bookings (Layer 2)
            $booking = Booking::create([
                'letter_number'        => $request->letter_number,
                'letter_slug'          => \Illuminate\Support\Str::slug(str_replace('/', '-', $request->letter_number)),
                'letter_file'          => $filePath,
                'user_id'              => $draftBooking->user_id,
                'vehicle_type'         => $draftBooking->vehicle_type,
                'vehicle_id'           => $draftBooking->vehicle_id,
                'driver_id'            => $draftBooking->driver_id,
                'vehicle_requirements' => $draftBooking->vehicle_requirements,
                'start_date'           => $draftBooking->start_date,
                'end_date'             => $draftBooking->end_date,
                'destination'          => $draftBooking->destination,
                'purpose'              => $draftBooking->purpose,
                'participants'         => $draftBooking->participants,
                'status'               => 'waiting', // Masuk ke alur Layer 2 persetujuan Approver
                'allocated_by'         => $draftBooking->reviewed_by,
                'allocated_at'         => $draftBooking->reviewed_at,
            ]);

            // 2. Ubah Status Draft menjadi Converted
            $draftBooking->update([
                'status' => 'converted',
            ]);
        });

        return redirect()->route('bookings.index')
            ->with('success', 'Surat Tugas berhasil diunggah! Permohonan telah resmi masuk ke alur SOP Peminjaman.');
    }
}