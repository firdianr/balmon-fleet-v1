<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Http\Requests;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::orderBy('code', 'asc')->get();

        return view('vehicles.index', compact('vehicles'));
    }

    public function adminIndex()
    {
        $vehicles = Vehicle::orderBy('code', 'asc')->get();

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('admin.vehicles.create');
    }

    public function store(StoreVehicleRequest $request)
    {
        // Mengambil data yang sudah lolos validasi dari StoreVehicleRequest
        $validated = $request->validated();

        // Handle Upload Foto Armada
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('vehicles', 'public');
        }

        // Simpan ke database
        Vehicle::create($validated);

        return redirect()->route('admin_app.vehicles.index')
            ->with('success', 'Armada kendaraan baru berhasil ditambahkan.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('admin.vehicles.edit', compact('vehicle'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        // 1. Ambil data yang sudah tervalidasi dari UpdateVehicleRequest
        $validated = $request->validated();

        // 2. Cek apakah ada perubahan status menjadi 'maintenance'
        $isChangingToMaintenance = isset($validated['status']) 
            && $validated['status'] === 'maintenance' 
            && $vehicle->status !== 'maintenance';

        $affectedBookingsCount = 0;

        if ($isChangingToMaintenance) {
            // Cari booking berstatus 'approved' yang belum berangkat (start_date >= hari ini)
            $affectedBookings = Booking::where('vehicle_id', $vehicle->id)
                ->where('status', 'approved')
                ->where('start_date', '>=', now()->startOfDay())
                ->get();

            $affectedBookingsCount = $affectedBookings->count();

            // Informasikan via WhatsApp ke Pemohon & Anggota Tim yang terdampak
            foreach ($affectedBookings as $booking) {
                $oldVehicleInfo = $vehicle->model . ' (' . $vehicle->plate_number . ')';

                // 📩 A. WhatsApp ke Pemohon Utama
                if (!empty($booking->user?->phone)) {
                    $msgApplicant = "⚠️ *PEMBERITAHUAN PERUBAHAN STATUS ARMADA*\n\n"
                        . "Mohon Maaf Bapak/Ibu *" . $booking->user->name . "*,\n"
                        . "Kami menginformasikan bahwa armada *" . $oldVehicleInfo . "* yang telah disetujui untuk perjalanan dinas Anda ke *" . $booking->destination . "* pada tanggal *" . $booking->start_date->format('d/m/Y') . "* saat ini masuk masa *PEMELIHARAAN (MAINTENANCE)*.\n\n"
                        . "Silakan koordinasi dengan Admin untuk dialihkan ke armada pengganti.";

                    WhatsAppService::sendMessage($booking->user->phone, $msgApplicant);
                }

                // 📩 B. WhatsApp ke Anggota Tim (via NIP)
                if (!empty($booking->participants)) {
                    $nips = [];
                    foreach ($booking->participants as $participant) {
                        if (preg_match('/NIP:\s*([0-9]+)/i', $participant, $matches)) {
                            $nips[] = $matches[1];
                        }
                    }

                    // UNCOMMENT LOGIK INI JIKA SUDAH SIAP KIRIM BULK MESSAGE KE ANGGOTA
                    // if (!empty($nips)) {
                    //     $memberPhones = User::whereIn('nip', $nips)->pluck('phone')->filter()->toArray();
                    //     if (!empty($memberPhones)) {
                    //         $msgMember = "⚠️ *PEMBERITAHUAN PERUBAHAN ARMADA*\n\n"
                    //             . "Armada *" . $oldVehicleInfo . "* untuk perjalanan dinas ke *" . $booking->destination . "* pada tanggal *" . $booking->start_date->format('d/m/Y') . "* saat ini masuk masa pemeliharaan (maintenance). Silakan hubungi Admin/Pemohon untuk info lebih lanjut.";
                    //         WhatsAppService::sendBulkMessage($memberPhones, $msgMember);
                    //     }
                    // }
                }
            }
        }

        // 3. Olah Foto jika ada unggahan baru
        if ($request->hasFile('photo')) {
            if ($vehicle->photo && Storage::disk('public')->exists($vehicle->photo)) {
                Storage::disk('public')->delete($vehicle->photo);
            }
            $validated['photo'] = $request->file('photo')->store('vehicles', 'public');
        }

        // 4. Update Data Kendaraan di Database
        $vehicle->update($validated);

        // 5. Redirection & Flash Messages
        $baseMessage = 'Data armada kendaraan ' . $vehicle->plate_number . ' berhasil diperbarui!';

        if ($affectedBookingsCount > 0) {
            $warningMessage = $baseMessage . " Terdapat {$affectedBookingsCount} peminjaman 'approved' yang terdampak dan notifikasi peringatan telah dikirim via WhatsApp.";
            
            return redirect()->route('admin_app.vehicles.index')
                ->with('warning', $warningMessage);
        }

        return redirect()->route('admin_app.vehicles.index')
            ->with('success', $baseMessage);
    }

    public function destroy(Vehicle $vehicle)
    {
        if ($vehicle->photo && Storage::disk('public')->exists($vehicle->photo)) {
            Storage::disk('public')->delete($vehicle->photo);
        }

        $vehicle->delete();

        return redirect()->route('admin_app.vehicles.index')
        ->with('success', 'Armada kendaraan ' . $vehicle->plate_number . ' berhasil dihapus.');
    }
}