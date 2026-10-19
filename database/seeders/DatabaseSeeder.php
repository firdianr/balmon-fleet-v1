<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Seed Accounts
        User::create([
            'name'       => 'Admin Aplikasi',
            'slug'       => 'admin-aplikasi',
            'email'      => 'adminapp@balmon.go.id',
            'nip'        => '198501012010011001',
            'password'   => Hash::make('password123'),
            'role'       => 'admin_app',
            'phone'      => '087761370145',
            'pangkat'    => 'Penata Muda (III/a)',
            'jabatan'    => 'Pengelola Sistem Informasi',
            'department' => 'Bagian Umum & Operasional',
        ]);

        User::create([
            'name'       => 'Admin Lapangan',
            'slug'       => 'admin-lapangan',
            'email'      => 'adminlap@balmon.go.id',
            'nip'        => '198805152015031002',
            'password'   => Hash::make('password123'),
            'role'       => 'admin_lap',
            'phone'      => '087761370145',
            'pangkat'    => 'Penata (III/c)',
            'jabatan'    => 'Koordinator Lapangan',
            'department' => 'Bagian Operasional',
        ]);

        User::create([
            'name'       => 'Approver',
            'slug'       => 'approver',
            'email'      => 'approver@balmon.go.id',
            'nip'        => '199008202018021003',
            'password'   => Hash::make('password123'),
            'role'       => 'approver',
            'phone'      => '087761370145',
            'pangkat'    => 'Pembina Tingkat I (IV/b)',
            'jabatan'    => 'Kepala Balai Monitor',
            'department' => 'Kepala Balai Monitor',
        ]);

        User::create([
            'name'       => 'Ahmad Pegawai',
            'slug'       => 'ahmad-pegawai',
            'email'      => 'pegawai@balmon.go.id',
            'nip'        => '199203152018021002',
            'password'   => Hash::make('password123'),
            'role'       => 'pegawai',
            'phone'      => '087761370145',
            'pangkat'    => 'Pengatur (II/c)',
            'jabatan'    => 'PFR Ahli Muda',
            'department' => 'Seksi Teknologi Informasi',
        ]);

        User::create([
            'name'       => 'Budi Driver',
            'slug'       => 'budi-driver',
            'email'      => 'driver@balmon.go.id',
            'nip'        => '199507202018021004',
            'password'   => Hash::make('password123'),
            'role'       => 'driver',
            'phone'      => '087761370145',
            'pangkat'    => 'PPPK Golongan V',
            'jabatan'    => 'Driver',
            'department' => 'Seksi Operasional',
        ]);

        // 2. Seed 10 Pegawai
        $this->call([
            UserSeeder::class,
        ]);

        // 3. Seed Armada Mobil
        Vehicle::create([
            'plate_number' => 'H 1234 AB',
            'slug' => 'h-1234-ab',
            'brand' => 'Toyota',
            'model' => 'Innova Zenix',
            'code' => 'A',
            'type' => 'operational',
            'capacity' => 7,
            'fuel_type' => 'Bensin/Hybrid',
            'status' => 'available',
            'notes' => 'Kondisi baik, servis rutin.',
        ]);

        Vehicle::create([
            'plate_number' => 'H 5678 CD',
            'slug' => 'h-5678-cd',
            'brand' => 'Toyota',
            'model' => 'Avanza Veloz',
            'code' => 'B',
            'type' => 'operational',
            'capacity' => 7,
            'fuel_type' => 'Bensin',
            'status' => 'available',
            'notes' => 'AC dingin, perlengkapan komplit.',
        ]);

        Vehicle::create([
            'plate_number' => 'H 9012 EF',
            'slug' => 'h-9012-ef',
            'brand' => 'Mitsubishi',
            'model' => 'Pajero Sport',
            'code' => 'C',
            'type' => 'operational',
            'capacity' => 7,
            'fuel_type' => 'Diesel',
            'status' => 'available',
            'notes' => 'Cocok untuk perjalanan lapangan luar kota.',
        ]);

        Vehicle::create([
            'plate_number' => 'H 3456 GH',
            'slug' => 'h-3456-gh',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'code' => 'D',
            'type' => 'monitoring',
            'capacity' => 5,
            'fuel_type' => 'Diesel',
            'status' => 'available',
            'notes' => 'Dilengkapi dengan peralatan ...',
        ]);

        Vehicle::create([
            'plate_number' => 'H 2837 KL',
            'slug' => 'h-2837-kl',
            'brand' => 'Isuzu',
            'model' => 'mu-X',
            'code' => 'E',
            'type' => 'monitoring',
            'capacity' => 5,
            'fuel_type' => 'Diesel',
            'status' => 'available',
            'notes' => 'Dilengkapi dengan peralatan ...',
        ]);
    }
}
