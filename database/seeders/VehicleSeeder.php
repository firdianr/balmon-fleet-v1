<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            [
                'plate_number' => 'H 1234 AB',
                'slug' => 'h-1234-ab',
                'brand' => 'Toyota',
                'model' => 'Innova Zenix',
                'code' => 'A',
                'type' => 'operational',
                'drivetrain' => '2wd',
                'capacity' => 7,
                'fuel_type' => 'Bensin/Hybrid',
                'status' => 'available',
                'notes' => 'Kondisi baik, servis rutin.',
            ],
            [
                'plate_number' => 'H 5678 CD',
                'slug' => 'h-5678-cd',
                'brand' => 'Toyota',
                'model' => 'Avanza Veloz',
                'code' => 'B',
                'type' => 'operational',
                'drivetrain' => '2wd',
                'capacity' => 7,
                'fuel_type' => 'Bensin',
                'status' => 'available',
                'notes' => 'AC dingin, perlengkapan komplit.',
            ],
            [
                'plate_number' => 'H 9012 EF',
                'slug' => 'h-9012-ef',
                'brand' => 'Mitsubishi',
                'model' => 'Pajero Sport',
                'code' => 'C',
                'type' => 'operational',
                'drivetrain' => '4wd',
                'capacity' => 7,
                'fuel_type' => 'Diesel',
                'status' => 'available',
                'notes' => 'Cocok untuk perjalanan lapangan luar kota.',
            ],
            [
                'plate_number' => 'H 3456 GH',
                'slug' => 'h-3456-gh',
                'brand' => 'Toyota',
                'model' => 'Hilux',
                'code' => 'D',
                'type' => 'monitoring',
                'drivetrain' => '4wd',
                'capacity' => 5,
                'fuel_type' => 'Diesel',
                'status' => 'available',
                'notes' => 'Dilengkapi dengan peralatan ...',
            ],
            [
                'plate_number' => 'H 2837 KL',
                'slug' => 'h-2837-kl',
                'brand' => 'Isuzu',
                'model' => 'mu-X',
                'code' => 'E',
                'type' => 'monitoring',
                'drivetrain' => '4wd',
                'capacity' => 5,
                'fuel_type' => 'Diesel',
                'status' => 'available',
                'notes' => 'Dilengkapi dengan peralatan ...',
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::updateOrCreate(
                ['plate_number' => $vehicle['plate_number']],
                $vehicle
            );
        }
    }
}