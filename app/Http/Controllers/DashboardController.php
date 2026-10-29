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

        // 1. Statistik Ringkasan Layer 1 & Layer 2
        $pendingDraftsCount = 0;
        if (in_array($user->role, ['admin_app', 'admin_lap'])) {
            $pendingDraftsCount = DraftBooking::where('status', 'draft')->count();
        }

        $pendingApprovalsCount = 0;
        if (in_array($user->role, ['admin_app', 'approver'])) {
            $pendingApprovalsCount = Booking::where('status', 'waiting')->count();
        }

        $activeTripsCount = Booking::whereIn('status', ['approved', 'on_trip'])->count();
        $totalVehiclesCount = Vehicle::count();
        $availableVehiclesCount = Vehicle::where('status', 'ready')->count();

        // 2. Data Pengajuan Terbaru Layer 1 (Draft)
        $recentDrafts = DraftBooking::with(['user', 'vehicle'])
            ->when(!in_array($user->role, ['admin_app', 'admin_lap', 'approver']), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->take(5)
            ->get();

        // 3. Data Peminjaman Resmi Terbaru Layer 2
        $recentBookings = Booking::with(['user', 'vehicle'])
            ->when(!in_array($user->role, ['admin_app', 'admin_lap', 'approver']), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'pendingDraftsCount',
            'pendingApprovalsCount',
            'activeTripsCount',
            'totalVehiclesCount',
            'availableVehiclesCount',
            'recentDrafts',
            'recentBookings'
        ));
    }
}