<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;
use App\Models\Booking;
use App\Models\VehicleLog;
use Illuminate\Support\Facades\DB;

class VehicleLogController extends Controller
{
    // Eksekusi Check-Out (Start Trip)
    public function processCheckOut(CheckOutRequest $request, Booking $booking)
    {
        $validated = $request->validated();

        if ($request->hasFile('start_photo')) {
            $validated['start_photo'] = $request->file('start_photo')->store('vehicle_photos', 'public');
        }

        DB::transaction(function () use ($booking, $validated) {
            // 1. Simpan Log
            VehicleLog::create([
                'booking_id'     => $booking->id,
                'vehicle_id'     => $booking->vehicle_id, // <-- TAMBAHKAN BARIS INI
                'start_km'       => $validated['start_km'],
                'start_fuel_level' => $validated['start_fuel_level'] ?? null,
                'start_photo'    => $validated['start_photo'] ?? null,
                'checked_out_at' => now(),
            ]);

            // 2. Update Status Booking & Mobil
            $booking->update(['status' => 'on_trip']);
            $booking->vehicle->update(['status' => 'borrowed']);
        });

        return redirect()->route('bookings.index')->with('success', 'Proses Check-Out berhasil. Selamat berkendara!');
    }

    // Eksekusi Check-In (End Trip)
    public function processCheckIn(CheckInRequest $request, Booking $booking)
    {
        $validated = $request->validated();
        $log = $booking->log;

        // Validation Logic: KM Akhir tidak boleh lebih kecil dari KM Awal
        if ($validated['end_km'] < $log->start_km) {
            return back()->withErrors(['end_km' => 'KM Akhir tidak boleh lebih kecil dari KM Awal (' . $log->start_km . ' KM).']);
        }

        if ($request->hasFile('end_photo')) {
            $validated['end_photo'] = $request->file('end_photo')->store('vehicle_photos', 'public');
        }

        DB::transaction(function () use ($booking, $log, $validated) {
            // 1. Update Log Pengembalian
            $log->update([
                'end_km' => $validated['end_km'],
                'fuel_level' => $validated['fuel_level'],
                'condition_notes' => $validated['condition_notes'] ?? null,
                'end_photo' => $validated['end_photo'] ?? null,
                'checked_in_at' => now(),
            ]);

            // 2. Update Status Booking & Mobil
            $booking->update(['status' => 'completed']);
            $booking->vehicle->update(['status' => 'available']);
        });

        return redirect()->route('bookings.index')->with('success', 'Mobil berhasil dikembalikan. Terima kasih!');
    }
}