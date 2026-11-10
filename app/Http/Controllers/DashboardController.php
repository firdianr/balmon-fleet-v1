<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DraftBooking;
use App\Models\Vehicle;
use App\Models\DriverAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. DATA STATISTIK UTAMA
        $totalVehiclesCount     = Vehicle::count();
        $availableVehiclesCount = Vehicle::where('status', 'available')->count();
        $activeTripsCount       = Booking::whereIn('status', ['approved', 'trip_started', 'on_trip', 'trip_completed'])->count();

        // ------------------------------------------------------------------
        // ROLE 1 & 2: ADMIN APP & ADMIN LAPANGAN
        // ------------------------------------------------------------------
        if (in_array($user->role, ['admin_app', 'admin_lap'])) {
            // 1. Permohonan Draft yang butuh alokasi unit/driver (Layer 1)
            $pendingDraftsCount = DraftBooking::where('status', 'draft')->count();
            
            // 2. Peminjaman Resmi yang BERSATU PENDING -> Butuh alokasi unit/driver oleh Admin Lapangan (Layer 2)
            $pendingBookingsCount = Booking::where('status', 'pending')->count();

            // 3. Peminjaman Resmi yang BERSTATU WAITING -> Butuh TTD Approver
            $pendingApprovalsCount = Booking::where('status', 'waiting')->count();

            // 4. Armada Kembali yang BERSTATU RETURNED -> Butuh Physical Check Garasi
            $pendingReturnVerificationsCount = Booking::where('status', 'returned')->count();

            // 5. Permohonan Cuti Driver Pending
            $pendingAttendancesCount = DriverAttendance::where('process_status', 'pending')->count();

            $recentDrafts       = DraftBooking::with(['user', 'vehicle', 'driver'])->latest()->take(5)->get();
            $recentBookings     = Booking::with(['user', 'vehicle', 'driver'])->latest()->take(5)->get();
            $pendingAttendances = DriverAttendance::where('process_status', 'pending')->with('driver')->take(5)->get();

            $viewName = $user->role === 'admin_app' ? 'dashboard.admin_app' : 'dashboard.admin_lap';

            return view($viewName, compact(
                'totalVehiclesCount',
                'availableVehiclesCount',
                'activeTripsCount',
                'pendingDraftsCount',
                'pendingBookingsCount',
                'pendingApprovalsCount',
                'pendingReturnVerificationsCount',
                'pendingAttendancesCount',
                'recentDrafts',
                'recentBookings',
                'pendingAttendances'
            ));
        }

        // ------------------------------------------------------------------
        // ROLE 3: APPROVER (Dua Tahap Persetujuan Dokumen: waiting & checked)
        // ------------------------------------------------------------------
        if ($user->role === 'approver') {
            $pendingDepartureCount       = Booking::where('status', 'waiting')->count();
            $pendingFinalCompletionCount = Booking::where('status', 'checked')->count();

            $pendingDepartureBookings = Booking::with(['user', 'vehicle', 'driver'])
                ->where('status', 'waiting')
                ->latest()
                ->take(5)
                ->get();

            $pendingCompletionBookings = Booking::with(['user', 'vehicle', 'driver'])
                ->where('status', 'checked')
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard.approver', compact(
                'activeTripsCount',
                'pendingDepartureCount',
                'pendingFinalCompletionCount',
                'pendingDepartureBookings',
                'pendingCompletionBookings'
            ));
        }

        // ------------------------------------------------------------------
        // ROLE 4: DRIVER (Mobile-First Operational & Attendance Focus)
        // ------------------------------------------------------------------
        if ($user->role === 'driver') {
            $driverActiveTrip = Booking::with(['vehicle', 'user'])
                ->where('driver_id', $user->id)
                ->whereIn('status', ['approved', 'trip_started', 'on_trip', 'trip_completed'])
                ->latest()
                ->first();

            $myHistory = Booking::with(['vehicle'])
                ->where('driver_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            $myAttendances = DriverAttendance::where('user_id', $user->id)->latest()->take(3)->get();

            return view('dashboard.driver', compact(
                'driverActiveTrip',
                'myHistory',
                'myAttendances'
            ));
        }

        // ------------------------------------------------------------------
        // ROLE 5: PEGAWAI / PEMOHON (Dua Tahap TTD Pengesahan: trip_started & trip_completed)
        // ------------------------------------------------------------------
        if ($user->role === 'pegawai') {
            $pemohonPendingDepartureSign = Booking::with(['vehicle', 'driver'])
                ->where('user_id', $user->id)
                ->where('status', 'trip_started')
                ->latest()
                ->first();

            $pemohonPendingReturnSign = Booking::with(['vehicle', 'driver'])
                ->where('user_id', $user->id)
                ->where('status', 'trip_completed')
                ->latest()
                ->first();

            $myDrafts = DraftBooking::with(['vehicle', 'driver'])
                ->where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            $myBookings = Booking::with(['vehicle', 'driver'])
                ->where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard.pegawai', compact(
                'availableVehiclesCount',
                'pemohonPendingDepartureSign',
                'pemohonPendingReturnSign',
                'myDrafts',
                'myBookings'
            ));
        }
    }
}