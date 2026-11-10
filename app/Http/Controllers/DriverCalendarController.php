<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DriverAttendance;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DriverCalendarController extends Controller
{
    public function index()
    {
        /** @var User $currentUser */$currentUser = Auth::user();

        // 1. Ambil daftar seluruh driver
        $drivers = User::where('role', 'driver')->orderBy('name')->get();

        // 2. Ambil data penugasan dinas
        $bookingQuery = Booking::with(['user', 'vehicle', 'driver'])
            ->whereNotNull('driver_id')
            ->whereIn('status', ['approved', 'on_trip', 'returned', 'checked', 'completed']);

        if ($currentUser->role === 'driver') {
            $bookingQuery->where('driver_id',$currentUser->id);
        }

        $events = $bookingQuery->get()->map(function ($b) {
            return [
                'id'            => $b->id,
                'type'          => 'booking',
                'driver_id'     => $b->driver_id,
                'driver_name'   => $b->driver->name ?? 'Driver',
                'user_name'     => $b->user->name ?? '-',
                'department'    => $b->user->department ?? '-',
                'vehicle_name'  => $b->vehicle ? ($b->vehicle->brand . ' ' .$b->vehicle->model) : 'Armada Operasional',
                'plate_number'  => $b->vehicle->plate_number ?? '-',
                'destination'   => $b->destination,
                'purpose'       => $b->purpose,
                'status'        => $b->status,
                'start_date'    => $b->start_date->format('Y-m-d'),
                'end_date'      => $b->end_date->format('Y-m-d'),
            ];
        });

        // 3. Ambil data ketidakhadiran (Cuti, Sakit, Izin, Service, Non-Aktif)
        $attendanceQuery = DriverAttendance::with(['driver', 'creator', 'actionBy']);

        if ($currentUser->role === 'driver') {
            $attendanceQuery->where('user_id',$currentUser->id);
        }

        $attendances = $attendanceQuery->get()->map(function ($att) {
            return [
                'id'              => $att->id,
                'type'            => 'attendance',
                'driver_id'       => $att->user_id,
                'driver_name'     => $att->driver->name ?? 'Driver',
                'status'          => $att->status,
                'process_status'  => $att->process_status,
                'attachment_url'  => $att->attachment_file ? Storage::url($att->attachment_file) : null,
                'notes'           => $att->notes ?? '-',
                'rejection_note'  => $att->rejection_note ?? '-',
                'created_by_name' => $att->creator->name ?? '-',
                'action_by_name'  => $att->actionBy->name ?? '-',
                'start_date'      => $att->start_date->format('Y-m-d'),
                'end_date'        => $att->end_date->format('Y-m-d'),
            ];
        });

        return view('calendar.drivers', compact('drivers', 'events', 'attendances', 'currentUser'));
    }

    public function storeAttendance(Request $request)
    {
        $currentUser = Auth::user();

        $validated = $request->validate([
            'user_id'         => ['required', 'exists:users,id'],
            'status'          => ['required', 'in:cuti,sakit,izin,service,non_active'],
            'start_date'      => ['required', 'date_format:Y-m-d'],
            'end_date'        => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'attachment_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $isDriver = $currentUser->role === 'driver';
        $processStatus = $isDriver ? 'pending' : 'approved';

        $filePath = null;
        if ($request->hasFile('attachment_file')) {
            $filePath = $request->file('attachment_file')->store('attendance_proofs', 'public');
        }
        
        // Set presisi jam 00:01:00 hingga 23:59:59
        $startDate = Carbon::parse($validated['start_date'])->setTime(0, 1, 0);  // 00:01:00
        $endDate   = Carbon::parse($validated['end_date'])->setTime(23, 59, 59); // 23:59:59

        $attendance = DriverAttendance::create([
            'user_id'         => $isDriver ? $currentUser->id : $validated['user_id'],
            'status'          => $validated['status'],
            'process_status'  => $processStatus,
            'attachment_file' => $filePath,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'notes'           => $validated['notes'] ?? null,
            'created_by'      => $currentUser->id,
            'action_by'       => $isDriver ? null : $currentUser->id,
            'action_at'       => $isDriver ? null : now(),
        ]);

        if ($isDriver) {
            $adminLap = User::whereIn('role', ['admin_lap', 'admin_app'])->whereNotNull('phone')->get();
            $msg = "📩 *PENGAJUAN IZIN/CUTI DRIVER BARU*\n\n"
                . "Driver *" . $currentUser->name . "* mengajukan " . strtoupper($attendance->status) . ".\n"
                . "📅 Periode: " . $attendance->start_date->format('d/m/Y') . " s/d " . $attendance->end_date->format('d/m/Y') . "\n"
                . "📝 Ket: " . ($attendance->notes ?? '-') . "\n\n"
                . "Mohon lakukan peninjauan dan persetujuan di Sistem Balmon Fleet.";

            foreach ($adminLap as $admin) {
                try {
                    WhatsAppService::sendMessage($admin->phone, $msg);
                } catch (\Exception $e) {}
            }
        }

        $msgSuccess = $isDriver 
            ? 'Pengajuan izin/cuti berhasil terkirim. Menunggu persetujuan Admin Lapangan.' 
            : 'Ketidakhadiran driver berhasil dicatat dan disahkan.';

        return back()->with('success', $msgSuccess);
    }

    public function updateAttendance(Request $request, $id)
    {
        $attendance = DriverAttendance::findOrFail($id);
        $currentUser = Auth::user();

        // Driver hanya boleh mengedit pengajuannya sendiri jika status masih 'pending'
        if ($currentUser->role === 'driver' && $attendance->user_id !== $currentUser->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah data ini.');
        }

        $validated = $request->validate([
            'status'          => ['required', 'in:cuti,sakit,izin,service,non_active'],
            'start_date'      => ['required', 'date'],
            'end_date'        => ['required', 'date', 'after_or_equal:start_date'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'attachment_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // Format start_date ke 00:01:00 dan end_date ke 23:59:59
        $validated['start_date'] = Carbon::parse($validated['start_date'])->setTime(0, 1, 0);
        $validated['end_date']   = Carbon::parse($validated['end_date'])->setTime(23, 59, 59);

        // Update lampiran file jika ada yang baru diunggah
        if ($request->hasFile('attachment_file')) {
            if ($attendance->attachment_file && Storage::disk('public')->exists($attendance->attachment_file)) {
                Storage::disk('public')->delete($attendance->attachment_file);
            }
            $validated['attachment_file'] = $request->file('attachment_file')->store('attendance_proofs', 'public');
        }

        $attendance->update($validated);

        return back()->with('success', 'Status ketidakhadiran driver berhasil diperbarui.');
    }

    public function approveAttendance(Request $request,$id)
    {
        $attendance = DriverAttendance::findOrFail($id);

        $request->validate([
            'process_status' => ['required', 'in:approved,rejected'],
            'rejection_note' => ['nullable', 'required_if:process_status,rejected', 'string', 'max:500'],
        ]);

        $attendance->update([
            'process_status' => $request->process_status,
            'rejection_note' => $request->rejection_note,
            'action_by'      => Auth::id(),
            'action_at'      => now(),
        ]);

        if ($attendance->driver && !empty($attendance->driver->phone)) {
            $statusText =$request->process_status === 'approved' ? 'DISETUJUI ✅' : 'DITOLAK ❌';
            $msg = "📌 *STATUS PENGAJUAN IZIN/CUTI DRIVER*\n\n"
                 . "Halo *" . $attendance->driver->name . "*,\n"
                 . "Pengajuan " . strtoupper($attendance->status) . " Anda periode (" . $attendance->start_date->format('d/m/Y') . ") telah *" . $statusText . "* oleh Admin.\n";

            if ($request->process_status === 'rejected') {$msg .= "Alasan Penolakan: " . $request->rejection_note;
            }

            try {
                WhatsAppService::sendMessage($attendance->driver->phone,$msg);
            } catch (\Exception $e) {}
        }

        return back()->with('success', 'Status pengajuan izin driver berhasil diproses.');
    }

    public function destroyAttendance($id)
    {
        $attendance = DriverAttendance::findOrFail($id);
        
        if ($attendance->attachment_file && Storage::disk('public')->exists($attendance->attachment_file)) {
            Storage::disk('public')->delete($attendance->attachment_file);
        }

        $attendance->delete();

        return back()->with('success', 'Status ketidakhadiran driver berhasil dibatalkan/dihapus.');
    }
}