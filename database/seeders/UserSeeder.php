<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $pegawaiList = [
            [
                'name'       => 'Budi Santoso, S.T.',
                'slug'       => 'budi-santoso',
                'nip'        => '198804122014021001',
                'email'      => 'budi.santoso@balmon.go.id',
                'phone'      => '081234567801',
                'department' => 'Seksi Sarana dan Pelayanan',
            ],
            [
                'name'       => 'Siti Rahmawati, S.Kom.',
                'slug'       => 'siti-rahmawati',
                'nip'        => '199008232015032002',
                'email'      => 'siti.rahmawati@balmon.go.id',
                'phone'      => '081234567802',
                'department' => 'Seksi Teknologi Informasi',
            ],
            [
                'name'       => 'Dwi Cahyono, A.Md.',
                'slug'       => 'dwi-cahyono',
                'nip'        => '199301152018011003',
                'email'      => 'dwi.cahyono@balmon.go.id',
                'phone'      => '081234567803',
                'department' => 'Seksi Penertiban & Penindakan',
            ],
            [
                'name'       => 'Eka Nurhayati, S.E.',
                'slug'       => 'eka-nurhayati',
                'nip'        => '199105192016022004',
                'email'      => 'eka.nurhayati@balmon.go.id',
                'phone'      => '081234567804',
                'department' => 'Subbagian Umum & Keuangan',
            ],
            [
                'name'       => 'Fajar Nugroho, S.T.',
                'slug'       => 'fajar-nugroho',
                'nip'        => '198711032012121005',
                'email'      => 'fajar.nugroho@balmon.go.id',
                'phone'      => '081234567805',
                'department' => 'Seksi Monitoring & Evaluasi',
            ],
            [
                'name'       => 'Gita Permata, S.H.',
                'slug'       => 'gita-permata',
                'nip'        => '199502282019032006',
                'email'      => 'gita.permata@balmon.go.id',
                'phone'      => '081234567806',
                'department' => 'Subbagian Hukum & Kepegawaian',
            ],
            [
                'name'       => 'Hendra Wijaya, S.Kom.',
                'slug'       => 'hendra-wijaya',
                'nip'        => '199207142017011007',
                'email'      => 'hendra.wijaya@balmon.go.id',
                'phone'      => '081234567807',
                'department' => 'Seksi Teknologi Informasi',
            ],
            [
                'name'       => 'Indah Lestari, A.Md.T.',
                'slug'       => 'indah-lestari',
                'nip'        => '199409052018022008',
                'email'      => 'indah.lestari@balmon.go.id',
                'phone'      => '081234567808',
                'department' => 'Seksi Sarana dan Pelayanan',
            ],
            [
                'name'       => 'Joko Prasetyo, S.T.',
                'slug'       => 'joko-prasetyo',
                'nip'        => '198903212015031009',
                'email'      => 'joko.prasetyo@balmon.go.id',
                'phone'      => '081234567809',
                'department' => 'Seksi Monitoring & Evaluasi',
            ],
            [
                'name'       => 'Kurnia Putri, S.E.',
                'slug'       => 'kurnia-putri',
                'nip'        => '199610102020122010',
                'email'      => 'kurnia.putri@balmon.go.id',
                'phone'      => '081234567810',
                'department' => 'Subbagian Umum & Keuangan',
            ],
        ];

        foreach ($pegawaiList as $pegawai) {
            User::firstOrCreate(
                ['nip' => $pegawai['nip']], // Mencegah duplikasi data jika seeder dijalankan ulang
                [
                    'name'       => $pegawai['name'],
                    'slug'       => $pegawai['slug'],
                    'email'      => $pegawai['email'],
                    'password'   => Hash::make('password123'), // Password default untuk seluruh pegawai
                    'role'       => 'pegawai',
                    'phone'      => $pegawai['phone'],
                    'department' => $pegawai['department'],
                ]
            );
        }
    }
}