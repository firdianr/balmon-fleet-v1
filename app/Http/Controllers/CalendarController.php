<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil seluruh data booking (termasuk pending, approved, on_trip, completed, rejected, canceled)
        $bookings = Booking::with(['vehicle', 'user'])
            ->orderBy('start_date', 'asc')
            ->get();

        // 2. Ambil seluruh data armada untuk pengecekan modal
        $vehicles = Vehicle::orderBy('code', 'asc')->get();

        // 3. Format data booking dengan proteksi Null Safety (?->)
        $events = $bookings->map(function ($booking) {
            // Format tanggal aman (mendukung string maupun Carbon)
            $startDate = $booking->start_date instanceof Carbon 
                ? $booking->start_date->format('Y-m-d') 
                : Carbon::parse($booking->start_date)->format('Y-m-d');

            $endDate = $booking->end_date instanceof Carbon 
                ? $booking->end_date->format('Y-m-d') 
                : Carbon::parse($booking->end_date)->format('Y-m-d');

            // Label status human-readable
            $statusLabel = match ($booking->status) {
                'pending'   => 'Menunggu Persetujuan',
                'approved'  => 'Disetujui (Siap Berangkat)',
                'on_trip'   => 'On Trip (Dalam Perjalanan)',
                'completed' => 'Selesai (Completed)',
                'rejected'  => 'Ditolak',
                'canceled'  => 'Dibatalkan',
                default     => ucfirst($booking->status),
            };

            return [
                'id'           => $booking->id,
                'title'        => $booking->vehicle 
                                    ? $booking->vehicle->brand . ' ' . $booking->vehicle->model . ' (' . $booking->vehicle->code . ')'
                                    : 'Armada Operasional (Belum Di-plot)',
                'vehicle_name' => $booking->vehicle 
                                    ? $booking->vehicle->brand . ' ' . $booking->vehicle->model 
                                    : 'Armada Operasional',
                'vehicle_code' => $booking->vehicle?->code ?? 'OP',
                'plate_number' => $booking->vehicle?->plate_number ?? '-',
                'vehicle_id'   => $booking->vehicle_id,
                'user_name'    => $booking->user?->name ?? 'Pegawai',
                'department'   => $booking->user?->department ?? '-',
                'destination'  => $booking->destination,
                'purpose'      => $booking->purpose,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
                'status'       => $booking->status,
                'status_label' => $statusLabel,
            ];
        });

        return view('calendar.index', [
            'events'   => $events,
            'vehicles' => $vehicles
        ]);
    }
}