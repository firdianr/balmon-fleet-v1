<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DraftBooking;
use App\Models\DriverAttendance;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        // ROLE 1: ADMIN APP (Master Overview & Dynamic System Health)
        // ------------------------------------------------------------------
        if ($user->role === 'admin_app') {
            $pendingDraftsCount              = DraftBooking::where('status', 'draft')->count();
            $pendingBookingsCount            = Booking::where('status', 'pending')->count();
            $pendingApprovalsCount           = Booking::where('status', 'waiting')->count();
            $pendingReturnVerificationsCount = Booking::where('status', 'returned')->count();
            $pendingAttendancesCount         = DriverAttendance::where('process_status', 'pending')->count();

            $recentDrafts       = DraftBooking::with(['user', 'vehicle', 'driver'])->latest()->take(5)->get();
            $recentBookings     = Booking::with(['user', 'vehicle', 'driver'])->latest()->take(5)->get();
            $pendingAttendances = DriverAttendance::where('process_status', 'pending')->with('driver')->take(5)->get();

            // Dynamic System Health Check
            $dbConnected = false;
            try {
                DB::connection()->getPdo();
                $dbConnected = true;
            } catch (\Exception $e) {
                $dbConnected = false;
            }

            $waConnected = false;
            try {
                $waConnected = !empty(config('services.whatsapp.api_key', env('PUSHOVER_TOKEN', true)));
            } catch (\Exception $e) {
                $waConnected = false;
            }

            $storageWritable = is_writable(storage_path('app/public'));

            $systemHealth = [
                'database' => $dbConnected,
                'whatsapp' => $waConnected,
                'storage'  => $storageWritable,
                'all_good' => $dbConnected && $waConnected && $storageWritable,
            ];

            return view('dashboard.admin_app', compact(
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
                'pendingAttendances',
                'systemHealth'
            ));
        }

        // ------------------------------------------------------------------
        // ROLE 2: ADMIN LAPANGAN (Operational, Allocation & Garage Check)
        // ------------------------------------------------------------------
        if ($user->role === 'admin_lap') {
            $pendingDraftsCount              = DraftBooking::where('status', 'draft')->count();
            $pendingBookingsCount            = Booking::where('status', 'pending')->count();
            $pendingApprovalsCount           = Booking::where('status', 'waiting')->count();
            $pendingReturnVerificationsCount = Booking::where('status', 'returned')->count();
            $pendingAttendancesCount         = DriverAttendance::where('process_status', 'pending')->count();

            $recentDrafts       = DraftBooking::with(['user', 'vehicle', 'driver'])->latest()->take(5)->get();
            $recentBookings     = Booking::with(['user', 'vehicle', 'driver'])->latest()->take(5)->get();
            $pendingAttendances = DriverAttendance::where('process_status', 'pending')->with('driver')->take(5)->get();

            return view('dashboard.admin_lap', compact(
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