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
                'plate_number' => 'H 1077 XW',
                'slug'         => 'h-1077-xw',
                'brand'        => 'Toyota',
                'model'        => 'Kijang Innova',
                'code'         => 'A',
                'type'         => 'operational',
                'drivetrain'   => '2wd',
                'capacity'     => 7,
                'fuel_type'    => 'Bensin',
                'status'       => 'available',
                'notes'        => 'Mobil operasional Kijang Innova.',
            ],
            [
                'plate_number' => 'H 1096 XW',
                'slug'         => 'h-1096-xw',
                'brand'        => 'Toyota',
                'model'        => 'Rush',
                'code'         => 'B',
                'type'         => 'operational',
                'drivetrain'   => '2wd',
                'capacity'     => 7,
                'fuel_type'    => 'Bensin',
                'status'       => 'available',
                'notes'        => 'Mobil operasional SUV Toyota Rush.',
            ],
            [
                'plate_number' => 'H 1204 XW',
                'slug'         => 'h-1204-xw',
                'brand'        => 'Toyota',
                'model'        => 'Kijang Innova',
                'code'         => 'C',
                'type'         => 'operational',
                'drivetrain'   => '2wd',
                'capacity'     => 7,
                'fuel_type'    => 'Bensin',
                'status'       => 'available',
                'notes'        => 'Mobil operasional Kijang Innova.',
            ],
            [
                'plate_number' => 'H 1330 XW',
                'slug'         => 'h-1330-xw',
                'brand'        => 'Toyota',
                'model'        => 'Innova Reborn',
                'code'         => 'D',
                'type'         => 'operational',
                'drivetrain'   => '2wd',
                'capacity'     => 7,
                'fuel_type'    => 'Bensin',
                'status'       => 'available',
                'notes'        => 'Mobil operasional Toyota Innova Reborn.',
            ],
            [
                'plate_number' => 'H 1337 XW',
                'slug'         => 'h-1337-xw',
                'brand'        => 'Toyota',
                'model'        => 'Kijang Innova',
                'code'         => 'E',
                'type'         => 'operational',
                'drivetrain'   => '2wd',
                'capacity'     => 7,
                'fuel_type'    => 'Bensin',
                'status'       => 'available',
                'notes'        => 'Mobil operasional Kijang Innova.',
            ],
            [
                'plate_number' => 'H 1338 XW',
                'slug'         => 'h-1338-xw',
                'brand'        => 'Toyota',
                'model'        => 'Kijang Innova 2.0 V',
                'code'         => 'F',
                'type'         => 'operational',
                'drivetrain'   => '2wd',
                'capacity'     => 7,
                'fuel_type'    => 'Bensin',
                'status'       => 'available',
                'notes'        => 'Mobil operasional Kijang Innova tipe V.',
            ],
            [
                'plate_number' => 'H 1356 XW',
                'slug'         => 'h-1356-xw',
                'brand'        => 'Isuzu',
                'model'        => 'mu-X 4x4',
                'code'         => 'G',
                'type'         => 'monitoring',
                'drivetrain'   => '4wd',
                'capacity'     => 5,
                'fuel_type'    => 'Diesel',
                'status'       => 'available',
                'notes'        => 'Mobil monitoring frekuensi dilengkapi peralatan observasi/rig.',
            ],
            [
                'plate_number' => 'H 8042 XW',
                'slug'         => 'h-8042-xw',
                'brand'        => 'Toyota',
                'model'        => 'Hilux',
                'code'         => 'H',
                'type'         => 'monitoring',
                'drivetrain'   => '4wd',
                'capacity'     => 5,
                'fuel_type'    => 'Diesel',
                'status'       => 'available',
                'notes'        => 'Mobil operasional/monitoring lapangan Toyota Hilux.',
            ],
            [
                'plate_number' => 'H 8162 XW',
                'slug'         => 'h-8162-xw',
                'brand'        => 'Toyota',
                'model'        => 'Hilux Monobs Specialized',
                'code'         => 'I',
                'type'         => 'monitoring',
                'drivetrain'   => '4wd',
                'capacity'     => 5,
                'fuel_type'    => 'Diesel',
                'status'       => 'available',
                'notes'        => 'Kendaraan khusus Monitoring Frekuensi Radio (Monobs).',
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