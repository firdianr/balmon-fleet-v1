<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DriverAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverCalendarController extends Controller
{
    public function index()
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // 1. Ambil daftar seluruh driver untuk dropdown/list
        $drivers = User::where('role', 'driver')->orderBy('name')->get();

        // 2. Ambil data penugasan dinas yang punya driver
        $bookingQuery = Booking::with(['user', 'vehicle', 'driver'])
            ->whereNotNull('driver_id')
            ->whereIn('status', ['approved', 'on_trip', 'returned', 'checked', 'completed']);

        if ($currentUser->role === 'driver') {
            $bookingQuery->where('driver_id', $currentUser->id);
        }

        $events = $bookingQuery->get()->map(function ($b) {
            return [
                'id'            => $b->id,
                'type'          => 'booking',
                'driver_id'     => $b->driver_id,
                'driver_name'   => $b->driver->name ?? 'Driver',
                'user_name'     => $b->user->name ?? '-',
                'department'    => $b->user->department ?? '-',
                'vehicle_name'  => $b->vehicle ? ($b->vehicle->brand . ' ' . $b->vehicle->model) : 'Armada Operasional',
                'plate_number'  => $b->vehicle->plate_number ?? '-',
                'destination'   => $b->destination,
                'purpose'       => $b->purpose,
                'status'        => $b->status,
                'start_date'    => $b->start_date->format('Y-m-d'),
                'end_date'      => $b->end_date->format('Y-m-d'),
            ];
        });

        // 3. Ambil data ketidakhadiran (Cuti, Sakit, Izin, Service, Non-Aktif)
        $attendanceQuery = DriverAttendance::with('driver');

        if ($currentUser->role === 'driver') {
            $attendanceQuery->where('user_id', $currentUser->id);
        }

        $attendances = $attendanceQuery->get()->map(function ($att) {
            return [
                'id'          => $att->id,
                'type'        => 'attendance',
                'driver_id'   => $att->user_id,
                'driver_name' => $att->driver->name ?? 'Driver',
                'status'      => $att->status, // cuti, sakit, izin, service, non_active
                'notes'       => $att->notes ?? '-',
                'start_date'  => $att->start_date->format('Y-m-d'),
                'end_date'    => $att->end_date->format('Y-m-d'),
            ];
        });

        return view('calendar.drivers', compact('drivers', 'events', 'attendances', 'currentUser'));
    }

    public function storeAttendance(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => ['required', 'exists:users,id'],
            'status'     => ['required', 'in:cuti,sakit,izin,service,non_active'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        DriverAttendance::create([
            'user_id'    => $validated['user_id'],
            'status'     => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'notes'      => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Status ketidakhadiran driver berhasil dicatat.');
    }

    public function updateAttendance(Request $request, $id)
    {
        $attendance = DriverAttendance::findOrFail($id);

        $validated = $request->validate([
            'status'     => ['required', 'in:cuti,sakit,izin,service,non_active'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->update($validated);

        return back()->with('success', 'Status ketidakhadiran driver berhasil diperbarui.');
    }

    public function destroyAttendance($id)
    {
        $attendance = DriverAttendance::findOrFail($id);
        $attendance->delete();

        return back()->with('success', 'Status ketidakhadiran driver berhasil dibatalkan.');
    }
}