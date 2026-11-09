<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DraftBooking;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. HITUNG STATISTIK & METRIK UTAMA
        // Total armada & armada siap pakai (sesuai enum DB: 'available')
        $totalVehiclesCount     = Vehicle::count();
        $availableVehiclesCount = Vehicle::where('status', 'available')->count();

        // Status permohonan draft (Layer 1) & peminjaman dinas (Layer 2)
        $pendingDraftsCount = 0;
        if (in_array($user->role, ['admin_app', 'admin_lap'])) {
            $pendingDraftsCount = DraftBooking::where('status', 'draft')->count();
        }

        $pendingApprovalsCount = 0;
        if (in_array($user->role, ['admin_app', 'approver'])) {
            $pendingApprovalsCount = Booking::where('status', 'waiting')->count();
        }

        // Armada kembali yang menunggu verifikasi fisik garasi oleh Admin Lapangan
        $pendingReturnVerificationsCount = 0;
        if (in_array($user->role, ['admin_app', 'admin_lap'])) {
            $pendingReturnVerificationsCount = Booking::where('status', 'returned')->count();
        }

        // Jumlah perjalanan armada yang sedang berlangsung di luar garasi
        $activeTripsCount = Booking::whereIn('status', ['approved', 'on_trip'])->count();

        // 2. ALERT PERHATIAN KHUSUS UNTUK DRIVER & PEMOHON
        $driverActiveTrip = null;
        if ($user->role === 'driver') {
            $driverActiveTrip = Booking::with(['vehicle', 'user'])
                ->where('driver_id', $user->id)
                ->whereIn('status', ['approved', 'on_trip'])
                ->latest()
                ->first();
        }

        $pemohonPendingReturnSign = null;
        if ($user->role === 'pemohon') {
            $pemohonPendingReturnSign = Booking::with(['vehicle'])
                ->where('user_id', $user->id)
                ->where('status', 'on_trip')
                ->whereHas('checklists', function ($q) {
                    $q->where('type', 'end');
                })
                ->latest()
                ->first();
        }

        // 3. DAFTAR PERMOHONAN DRAFT TERBARU (LAYER 1)
        $recentDrafts = DraftBooking::with(['user', 'vehicle', 'driver'])
            ->when(!in_array($user->role, ['admin_app', 'admin_lap', 'approver']), function ($q) use ($user) {
                if ($user->role === 'driver') {
                    $q->where('driver_id', $user->id);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->latest()
            ->take(5)
            ->get();

        // 4. DAFTAR PEMINJAMAN RESMI TERBARU (LAYER 2)
        $recentBookings = Booking::with(['user', 'vehicle', 'driver'])
            ->when(!in_array($user->role, ['admin_app', 'admin_lap', 'approver']), function ($q) use ($user) {
                if ($user->role === 'driver') {
                    $q->where('driver_id', $user->id);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalVehiclesCount',
            'availableVehiclesCount',
            'pendingDraftsCount',
            'pendingApprovalsCount',
            'pendingReturnVerificationsCount',
            'activeTripsCount',
            'driverActiveTrip',
            'pemohonPendingReturnSign',
            'recentDrafts',
            'recentBookings'
        ));
    }
}