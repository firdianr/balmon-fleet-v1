<?php

namespace Database\Seeders;

use App\Models\User;
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
            'name'       => 'Andi Driver',
            'slug'       => 'andi-driver',
            'email'      => 'driver@balmon.go.id',
            'nip'        => '199507202018021004',
            'password'   => Hash::make('password123'),
            'role'       => 'driver',
            'phone'      => '087761370145',
            'pangkat'    => 'PPPK Golongan V',
            'jabatan'    => 'Driver',
            'department' => 'Seksi Operasional',
        ]);

        $this->call([
            UserSeeder::class,
            VehicleSeeder::class,
            MasterChecklistSeeder::class,
        ]);
    }
}
