<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DraftBooking;
use App\Models\DriverAttendance;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

            // Pengecekan Dinamis WA Gateway (Baileys Docker)
            $waConnected = false;
            $waStatusDetail = [
                'connected' => false,
                'state'     => 'offline',
                'pending_messages' => 0,
                'message'   => 'Tidak dapat terhubung ke WA Gateway',
                'user'      => null,
            ];

            try {
                $waHost = rtrim(config('services.wa_gateway.host', 'http://localhost'), '/');
                $waPort = config('services.wa_gateway.port', '3000');

                // Timeout 2 detik agar dashboard tetap cepat meski Docker mati
                $response = Http::timeout(2)->get("{$waHost}:{$waPort}/status");

                if ($response->successful()) {
                    $data = $response->json();
                    $state = $data['state'] ?? 'unknown';
                    $pendingCount = (int) ($data['pending_messages'] ?? 0);
                    $queueText    = $pendingCount > 0 ? " ({$pendingCount} pesan tertunda)" : '';

                    if (!empty($data['connected']) && $state === 'open') {
                        $waConnected = true;
                        $waStatusDetail = [
                            'connected' => true,
                            'state'     => 'open',
                            'pending_messages' => $pendingCount,
                            'message'   => 'Terhubung (' . ($data['user']['name'] ?? 'Aktif') . ')' . $queueText,
                            'user'      => $data['user'] ?? null,
                        ];
                    } elseif ($state === 'qr') {
                        $waStatusDetail = [
                            'connected' => false,
                            'state'     => 'qr',
                            'pending_messages' => $pendingCount,
                            'message'   => 'Menunggu Scan QR Code di Terminal Docker',
                            'user'      => null,
                        ];
                    } elseif ($state === 'connecting' || $state === 'starting') {
                        $waStatusDetail = [
                            'connected' => false,
                            'state'     => $state,
                            'pending_messages' => $pendingCount,
                            'message'   => 'Sedang mencoba menghubungkan ke server WhatsApp...',
                            'user'      => null,
                        ];
                    } else {
                        $waStatusDetail = [
                            'connected' => false,
                            'state'     => $state,
                            'pending_messages' => $pendingCount,
                            'message'   => 'Sesi WhatsApp terputus (Logged Out / Closed)',
                            'user'      => null,
                        ];
                    }
                } else {
                    $waStatusDetail['message'] = 'WA Gateway merespons error HTTP ' . $response->status();
                }
            } catch (ConnectionException $e) {
                $waStatusDetail = [
                    'connected' => false,
                    'state'     => 'offline',
                    'pending_messages' => 0,
                    'message'   => 'Container Docker WA Gateway mati / tidak terjangkau di port ' . ($waPort ?? '3000'),
                    'user'      => null,
                ];
            } catch (\Exception $e) {
                $waStatusDetail = [
                    'connected' => false,
                    'state'     => 'error',
                    'pending_messages' => 0,
                    'message'   => 'Error internal: ' . $e->getMessage(),
                    'user'      => null,
                ];
            }

            $storageWritable = is_writable(storage_path('app/public'));

            $systemHealth = [
                'database'        => $dbConnected,
                'whatsapp'        => $waConnected,       // Tetap boolean agar tidak merusak view lama Anda
                'whatsapp_detail' => $waStatusDetail,    // Data dinamis untuk ditampilkan di UI/tooltip
                'storage'         => $storageWritable,
                'all_good'        => $dbConnected && $waConnected && $storageWritable,
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