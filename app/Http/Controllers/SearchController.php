<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function bookings(Request $request)
    {
        $q = $request->get('q', '');
        $sort = $request->get('sort', 'upcoming'); // Default: Jadwal Terdekat

        $query = Booking::with(['user', 'vehicle', 'log'])
            ->where(function ($query) use ($q) {
                $query->where('letter_number', 'LIKE', "%{$q}%")
                    ->orWhere('destination', 'LIKE', "%{$q}%")
                    ->orWhere('purpose', 'LIKE', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'LIKE', "%{$q}%")
                            ->orWhere('nip', 'LIKE', "%{$q}%");
                    })
                    ->orWhereHas('vehicle', function ($v) use ($q) {
                        $v->where('brand', 'LIKE', "%{$q}%")
                            ->orWhere('model', 'LIKE', "%{$q}%")
                            ->orWhere('plate_number', 'LIKE', "%{$q}%");
                    });
            });

        // Logika Sort
        switch ($sort) {
            case 'created_latest':
                $query->latest(); // Terbaru dibuat
                break;
            case 'start_desc':
                $query->orderBy('start_date', 'desc'); // Tanggal jalan terjauh
                break;
            case 'upcoming':
            default:
                $query->orderBy('start_date', 'asc'); // Tanggal jalan terdekat
                break;
        }

        $bookings = $query->paginate(10);

        // Format tanggal dan sertakan KM Terakhir Mobil untuk Modal Check-Out
        $bookings->getCollection()->transform(function ($booking) {
            $booking->formatted_start_date = $booking->start_date ? $booking->start_date->format('d M Y H:i') : '-';
            $booking->formatted_end_date = $booking->end_date ? $booking->end_date->format('d M Y H:i') : '-';

            // Ambil KM terakhir kendaraan jika kendaraan tersedia
            if ($booking->vehicle) {
                $lastLog = VehicleLog::where('vehicle_id', $booking->vehicle_id)->latest()->first();
                $booking->vehicle->last_km = $lastLog ? ($lastLog->end_km ?? $lastLog->start_km) : 0;
            }

            return $booking;
        });

        return response()->json($bookings);
    }

    // Search Vehicles
    public function vehicles(Request $request)
    {
        $q = $request->get('q', '');

        $vehicles = Vehicle::where(function ($query) use ($q) {
                $query->where('brand', 'LIKE', "%{$q}%")
                    ->orWhere('model', 'LIKE', "%{$q}%")
                    ->orWhere('plate_number', 'LIKE', "%{$q}%")
                    ->orWhere('fuel_type', 'LIKE', "%{$q}%")
                    ->orWhere('status', 'LIKE', "%{$q}%");
            })
            ->latest()
            ->paginate(10);

        return response()->json($vehicles);
    }

    // Search Users
    public function users(Request $request)
    {
        $q = $request->get('q', '');
        $sort = $request->get('sort', 'name_asc'); // Default A-Z

        $query = User::where(function ($query) use ($q) {
            $query->where('name', 'LIKE', "%{$q}%")
                ->orWhere('nip', 'LIKE', "%{$q}%")
                ->orWhere('email', 'LIKE', "%{$q}%")
                ->orWhere('role', 'LIKE', "%{$q}%")
                ->orWhere('department', 'LIKE', "%{$q}%");
        });

        // Menerapkan logika pengurutan
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'nip_asc':
                $query->orderBy('nip', 'asc');
                break;
            case 'latest':
                $query->latest();
                break;
            case 'oldest':
                $query->oldest();
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $users = $query->paginate(10);

        return response()->json($users);
    }
}