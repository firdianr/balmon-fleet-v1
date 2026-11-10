<?php

namespace App\Http\Controllers;

use App\Models\DraftBooking;
use App\Models\DriverAttendance;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DraftBookingController extends Controller
{
    /**
     * Menampilkan daftar draft permohonan armada (Layer 1).
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $drafts = DraftBooking::with(['user', 'vehicle', 'driver', 'reviewer', 'booking'])
            // Jika bukan Admin (app/lap) atau Approver, filter hanya untuk Pemohon ATAU Driver yang ditunjuk
            ->when(!in_array($user->role, ['admin_app', 'admin_lap', 'approver']), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('driver_id', $user->id);
                });
            })
            ->latest()
            ->paginate(10);

        // Master data kendaraan dan driver untuk modal review Admin Lapangan
        $vehicles = Vehicle::where('status', '!=', 'maintenance')
            ->orderBy('code', 'asc')
            ->get();

        $drivers = User::where('role', 'driver')
            ->orderBy('name', 'asc')
            ->get();

        $attendances = DriverAttendance::where('process_status', 'approved')
            ->where('end_date', '>=', Carbon::today())
            ->get(['id', 'user_id', 'start_date', 'end_date', 'status']); // Select kolom yang dibutuhkan saja

        return view('draft_bookings.index', compact('drafts', 'vehicles', 'drivers', 'attendances'));
    }

    /**
     * Menampilkan formulir pengajuan draft permohonan baru.
     */
    public function create()
    {
        $vehicles = Vehicle::where('status', '!=', 'maintenance')
            ->orderBy('type', 'asc')
            ->orderBy('brand', 'asc')
            ->get();

        $drivers = User::where('role', 'driver')
            ->orderBy('name', 'asc')
            ->get();

        return view('draft_bookings.create', compact('vehicles', 'drivers'));
    }

    /**
     * Menyimpan data draft permohonan armada baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'driver_id'  => ['nullable', 'exists:users,id'],
            'drivetrain' => ['required', 'in:2wd,4wd'],
            'vehicle_id' => ['nullable', 'required_if:drivetrain,4wd', 'exists:vehicles,id'],
        ], [
            'start_date.required'       => 'Waktu mulai peminjaman wajib diisi.',
            'start_date.after_or_equal' => 'Waktu mulai minimal hari ini.',
            'end_date.required'         => 'Waktu selesai peminjaman wajib diisi.',
            'end_date.after_or_equal'   => 'Waktu selesai tidak boleh sebelum waktu mulai.',
            'drivetrain.required'       => 'Pilih sistem penggerak roda (2WD / 4WD).',
            'vehicle_id.required_if'    => 'Silakan pilih unit kendaraan untuk kebutuhan 4WD.',
            'vehicle_id.exists'         => 'Unit kendaraan tidak valid.',
            'driver_id.exists'          => 'Driver tidak terdaftar.',
        ]);

        $vehicle = !empty($validated['vehicle_id']) ? Vehicle::find($validated['vehicle_id']) : null;

        $validated['user_id']      = Auth::id();
        $validated['vehicle_type'] = $vehicle ? $vehicle->type : 'operational';
        $validated['status']       = 'draft';

        $draft = DraftBooking::create($validated);

        // Notifikasi WhatsApp ke Admin Lapangan
        $adminPhones = User::where('role', 'admin_lap')->pluck('phone')->filter()->toArray();
        if (!empty($adminPhones)) {
            $startDate   = Carbon::parse($draft->start_date)->format('d/m/Y H:i');
            $endDate     = Carbon::parse($draft->end_date)->format('d/m/Y H:i');
            $vehicleText = $vehicle ? "{$vehicle->brand} {$vehicle->model} ({$vehicle->plate_number})" : "Akan Ditentukan Admin Lapangan";

            $msgAdmin = "📝 *PERMOHONAN DRAFT ARMADA BARU*\n\n"
                . "Pemohon: " . Auth::user()->name . "\n"
                . "Penggerak Roda: " . strtoupper($draft->drivetrain) . "\n"
                . "Unit Armada: {$vehicleText}\n"
                . "Jadwal: {$startDate} s.d. {$endDate}\n\n"
                . "Mohon tinjau permohonan ini pada sistem Balmon Fleet.";

            WhatsAppService::sendBulkMessage($adminPhones, $msgAdmin);
        }

        return redirect()->route('draft-bookings.index')
            ->with('success', 'Permohonan draft armada berhasil diajukan! Menunggu peninjauan Admin Lapangan.');
    }

    /**
     * Menampilkan formulir edit draft permohonan.
     */
    public function edit(DraftBooking $draftBooking)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($draftBooking->user_id !== $user->id && !in_array($user->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah draft permohonan ini.');
        }

        if ($draftBooking->status === 'converted') {
            return redirect()->route('draft-bookings.index')
                ->with('error', 'Draft yang sudah dikonversi menjadi peminjaman resmi tidak dapat diubah.');
        }

        $vehicles = Vehicle::where('status', '!=', 'maintenance')
            ->orderBy('type', 'asc')
            ->orderBy('brand', 'asc')
            ->get();

        $drivers = User::where('role', 'driver')
            ->orderBy('name', 'asc')
            ->get();

        return view('draft_bookings.edit', compact('draftBooking', 'vehicles', 'drivers'));
    }

    /**
     * Memperbarui data draft permohonan armada.
     */
    public function update(Request $request, DraftBooking $draftBooking)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($draftBooking->user_id !== $user->id && !in_array($user->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Anda tidak memiliki hak akses mengubah draft ini.');
        }

        if ($draftBooking->status === 'converted') {
            return redirect()->route('draft-bookings.index')
                ->with('error', 'Draft yang sudah dikonversi menjadi peminjaman resmi tidak dapat diubah.');
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'driver_id'  => ['nullable', 'exists:users,id'],
            'drivetrain' => ['required', 'in:2wd,4wd'],
            'vehicle_id' => ['nullable', 'required_if:drivetrain,4wd', 'exists:vehicles,id'],
        ], [
            'start_date.required'       => 'Waktu mulai peminjaman wajib diisi.',
            'start_date.after_or_equal' => 'Waktu mulai minimal hari ini.',
            'end_date.required'         => 'Waktu selesai peminjaman wajib diisi.',
            'end_date.after_or_equal'   => 'Waktu selesai tidak boleh sebelum waktu mulai.',
            'drivetrain.required'       => 'Pilih sistem penggerak roda (2WD / 4WD).',
            'vehicle_id.required_if'    => 'Silakan pilih unit kendaraan untuk kebutuhan 4WD.',
            'vehicle_id.exists'         => 'Unit kendaraan tidak valid.',
            'driver_id.exists'          => 'Driver tidak terdaftar.',
        ]);

        $vehicle = !empty($validated['vehicle_id']) ? Vehicle::find($validated['vehicle_id']) : null;

        $validated['vehicle_id']   = $vehicle ? $vehicle->id : null;
        $validated['vehicle_type'] = $vehicle ? $vehicle->type : 'operational';

        $draftBooking->update($validated);

        return redirect()->route('draft-bookings.index')
            ->with('success', 'Data draft permohonan armada berhasil diperbarui.');
    }

    /**
     * Peninjauan / Persetujuan ketersediaan armada oleh Admin Lapangan.
     */
    public function review(Request $request, DraftBooking $draftBooking)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!in_array($user->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Anda tidak memiliki akses peninjauan.');
        }

        $validated = $request->validate([
            'action'     => ['required', 'in:approve,reject'],
            'vehicle_id' => ['nullable', 'required_if:action,approve', 'exists:vehicles,id'],
            'driver_id'  => ['nullable', 'exists:users,id'],
            'admin_note' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ], [
            'vehicle_id.required_if' => 'Silakan alokasikan unit kendaraan sebelum menyetujui.',
            'admin_note.required_if' => 'Alasan penolakan wajib diisi jika Anda menolak permohonan.',
        ]);

        if ($validated['action'] === 'approve') {
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);

            $draftBooking->update([
                'status'       => 'approved',
                'vehicle_id'   => $vehicle->id,
                'vehicle_type' => $vehicle->type,
                'driver_id'    => $validated['driver_id'] ?? null,
                'admin_note'   => $validated['admin_note'] ?? null,
                'reviewed_by'  => $user->id,
                'reviewed_at'  => now(),
            ]);

            $msg = 'Permohonan draft armada berhasil disetujui dan alokasi unit telah disimpan.';

            // --- 1. NOTIFIKASI WA KE PEMOHON ---
            if (!empty($draftBooking->user?->phone)) {
                $driverName = $draftBooking->driver ? $draftBooking->driver->name : 'Lepas Kunci (Tanpa Driver)';
                
                $waMessagePemohon = "✅ *PERMOHONAN DRAFT ARMADA DISETUJUI*\n\n"
                    . "Halo *" . $draftBooking->user->name . "*,\n"
                    . "Permohonan armada Anda melalui Draft telah ditinjau dan **DISETUJUI** oleh Admin Lapangan (" . $user->name . ").\n\n"
                    . "📋 *Rincian Alokasi:*\n"
                    . "• Unit Armada: *" . $vehicle->brand . " " . $vehicle->model . " (" . $vehicle->plate_number . ")*\n"
                    . "• Pengemudi: *" . $driverName . "*\n\n"
                    . "Silakan lanjutkan ke tahap pengajuan **Peminjaman Dinas (Formal Booking)** melalui sistem Balmon Fleet.";

                WhatsAppService::sendMessage($draftBooking->user->phone, $waMessagePemohon);
            }

            // --- 2. NOTIFIKASI WA KE DRIVER YANG DITUNJUK ---
            if ($draftBooking->driver_id && !empty($draftBooking->driver?->phone)) {
                $waMessageDriver = "🚘 *PENUGASAN DRAFT ARMADA BARU*\n\n"
                    . "Halo *" . $draftBooking->driver->name . "*,\n"
                    . "Anda telah ditunjuk oleh Admin Lapangan (" . $user->name . ") sebagai pengemudi untuk jadwal permohonan draft armada berikut:\n\n"
                    . "📋 *Rincian Penugasan:*\n"
                    . "• Pemohon: *" . $draftBooking->user->name . "*\n"
                    . "• Unit Armada: *" . $vehicle->brand . " " . $vehicle->model . " (" . $vehicle->plate_number . ")*\n"
                    . "• Tujuan: *" . $draftBooking->destination . "*\n"
                    . "• Tanggal: *" . date('d/m/Y H:i', strtotime($draftBooking->start_date)) . " s.d. " . date('d/m/Y H:i', strtotime($draftBooking->end_date)) . "*\n\n"
                    . "Mohon persiapkan diri dan pantau pembaruan status peminjaman formal selanjutnya di sistem Balmon Fleet.";

                WhatsAppService::sendMessage($draftBooking->driver->phone, $waMessageDriver);
            }

        } else {
            $draftBooking->update([
                'status'      => 'rejected',
                'admin_note'  => $validated['admin_note'],
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            $msg = 'Permohonan draft armada telah ditolak.';

            // --- NOTIFIKASI WA SAAT DITOLAK ---
            if (!empty($draftBooking->user?->phone)) {
                $waMessage = "❌ *PERMOHONAN DRAFT ARMADA DITOLAK*\n\n"
                    . "Halo *" . $draftBooking->user->name . "*,\n"
                    . "Mohon maaf, permohonan armada Anda melalui Draft telah **DITOLAK** oleh Admin Lapangan.\n\n"
                    . "📝 *Alasan Penolakan:*\n"
                    . "\"" . $validated['admin_note'] . "\"\n\n"
                    . "Silakan periksa kembali permohonan Anda atau hubungi Admin Lapangan untuk informasi lebih lanjut.";

                WhatsAppService::sendMessage($draftBooking->user->phone, $waMessage);
            }
        }

        return redirect()->route('draft-bookings.index')->with('success', $msg);
    }

    /**
     * Menghapus draft permohonan armada.
     */
    public function destroy(DraftBooking $draftBooking)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($draftBooking->user_id !== $user->id && !in_array($user->role, ['admin_app', 'admin_lap'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus draft ini.');
        }

        if ($draftBooking->status === 'converted') {
            return redirect()->route('draft-bookings.index')
                ->with('error', 'Draft yang telah dikonversi menjadi peminjaman resmi tidak dapat dihapus.');
        }

        $draftBooking->delete();

        return redirect()->route('draft-bookings.index')
            ->with('success', 'Draft permohonan armada berhasil dihapus.');
    }
}