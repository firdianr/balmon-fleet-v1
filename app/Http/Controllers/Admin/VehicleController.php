<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index()
    {
        // Ambil mobil diurutkan berdasarkan brand (A-Z), lalu model (A-Z)
        $vehicles = Vehicle::orderBy('brand', 'asc')
            ->orderBy('model', 'asc')
            ->with(['bookings' => function($q) {
                $q->whereHas('log')->with('log')->latest();
            }])
            ->get();

        return view('vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('admin.vehicles.create');
    }

    public function store(StoreVehicleRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('vehicles', 'public');
        }

        Vehicle::create($validated);

        return redirect()->route('admin.vehicles.index')->with('success', 'Armada kendaraan berhasil ditambahkan.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('admin.vehicles.edit', compact('vehicle'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            if ($vehicle->photo) {
                Storage::disk('public')->delete($vehicle->photo);
            }
            $validated['photo'] = $request->file('photo')->store('vehicles', 'public');
        }

        $vehicle->update($validated);

        return redirect()->route('admin.vehicles.index')
        ->with('success', 'Data armada kendaraan ' . $vehicle->plate_number . ' berhasil diperbarui!');
    }

    public function destroy(Vehicle $vehicle)
    {
        if ($vehicle->photo) {
            Storage::disk('public')->delete($vehicle->photo);
        }

        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')
        ->with('success', 'Armada kendaraan ' . $vehicle->plate_number . ' berhasil dihapus.');
    }
}