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
            'name'       => 'Admin Bagian Umum',
            'slug'       => 'admin-bagian-umum',
            'email'      => 'admin@kantor.go.id',
            'nip'        => '198501012010011001',
            'password'   => Hash::make('password123'),
            'role'       => 'admin',
            'phone'      => '081234567890',
            'department' => 'Bagian Umum & Operasional',
        ]);

        User::create([
            'name'       => 'Ahmad Pegawai',
            'slug'       => 'ahmad-pegawai',
            'email'      => 'pegawai@kantor.go.id',
            'nip'        => '199203152018021002',
            'password'   => Hash::make('password123'),
            'role'       => 'pegawai',
            'phone'      => '089876543210',
            'department' => 'Seksi Teknologi Informasi',
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
            'capacity' => 7,
            'fuel_type' => 'Diesel',
            'status' => 'available',
            'notes' => 'Cocok untuk perjalanan lapangan luar kota.',
        ]);
    }
}
