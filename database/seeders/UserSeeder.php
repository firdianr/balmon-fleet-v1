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
                'name'       => 'Kuswahyudi, S.Kom., M.M.',
                'slug'       => 'kuswahyudi',
                'nip'        => '198305142006041024',
                'email'      => 'kuswahyudi@balmon.go.id',
                'phone'      => '085169427857',
                'pangkat'    => 'Pembina (IV/a)',
                'jabatan'    => 'Pengendali Frekuensi Radio',
                'department' => 'Sarana dan Prasarana',
            ],
            [
                'name'       => 'Ratna Mumpuni, S.T., M.Eng',
                'slug'       => 'ratna-mumpuni',
                'nip'        => '197902242008032001',
                'email'      => 'ratna.mumpuni@balmon.go.id',
                'phone'      => '085169427857',
                'pangkat'    => 'Pembina (IV/a)',
                'jabatan'    => 'Penata Layanan Operasional',
                'department' => 'Seksi Teknologi Informasi',
            ],
            [
                'name'       => 'Septi Andi Eka Wibowo, S.T., M.T.',
                'slug'       => 'septi-andi-eka-wibowo',
                'nip'        => '198609162009121001',
                'email'      => 'septi.andi.eka.wibowo@balmon.go.id',
                'phone'      => '085169427857',
                'pangkat'    => 'Penata Tk. I (III/d)',
                'jabatan'    => 'Penyidik Pelaksana',
                'department' => 'Tim Monitoring dan Penertiban Spektrum Frekuensi Radio',
            ],
            [
                'name'       => 'Alfido Indrawan, A.Md.T',
                'slug'       => 'alfido-indrawan',
                'nip'        => '200008232025041003',
                'email'      => 'alfido.indrawan@balmon.go.id',
                'phone'      => '085169427857',
                'pangkat'    => 'Pengatur (II/c)',
                'jabatan'    => 'Pranata Keuangan APBN Penyelia',
                'department' => 'Subbagian Umum & Keuangan',
            ],
            [
                'name'       => 'Ardiansyah',
                'slug'       => 'ardiansyah',
                'nip'        => '199109252025211035',
                'email'      => 'ardiansyah@balmon.go.id',
                'phone'      => '085169427857',
                'pangkat'    => 'Pengatur Muda PPPK V',
                'jabatan'    => 'PFR Ahli Muda',
                'department' => 'Tim Monitoring dan Penertiban Spektrum Frekuensi Radio',
            ],
            [
                'name'       => 'Gita Permata, S.H.',
                'slug'       => 'gita-permata',
                'nip'        => '199502282019032006',
                'email'      => 'gita.permata@balmon.go.id',
                'phone'      => '081234567806',
                'pangkat'    => 'Penata Muda Tk. I (III/b)',
                'jabatan'    => 'Penelaah Teknis Kebijakan',
                'department' => 'Subbagian Hukum & Kepegawaian',
            ],
            [
                'name'       => 'Hendra Wijaya, S.Kom.',
                'slug'       => 'hendra-wijaya',
                'nip'        => '199207142017011007',
                'email'      => 'hendra.wijaya@balmon.go.id',
                'phone'      => '081234567807',
                'pangkat'    => 'Penata Muda Tk. I (III/b)',
                'jabatan'    => 'PFR Ahli Muda',
                'department' => 'Seksi Teknologi Informasi',
            ],
            [
                'name'       => 'Indah Lestari, A.Md.T.',
                'slug'       => 'indah-lestari',
                'nip'        => '199409052023212008',
                'email'      => 'indah.lestari@balmon.go.id',
                'phone'      => '081234567808',
                'pangkat'    => 'PPPK Golongan VII',
                'jabatan'    => 'Penata Layanan Operasional',
                'department' => 'Tim Sarana dan Prasarana Monitoring dan Layanan',
            ],
            [
                'name'       => 'Joko Prasetyo, S.T.',
                'slug'       => 'joko-prasetyo',
                'nip'        => '198903212015031009',
                'email'      => 'joko.prasetyo@balmon.go.id',
                'phone'      => '081234567809',
                'pangkat'    => 'Penata (III/c)',
                'jabatan'    => 'PFR Ahli Muda',
                'department' => 'Tim Kualitas Layanan Infrastruktur Digital',
            ],
            [
                'name'       => 'Kurnia Putri, S.E.',
                'slug'       => 'kurnia-putri',
                'nip'        => '199610102023212010',
                'email'      => 'kurnia.putri@balmon.go.id',
                'phone'      => '081234567810',
                'pangkat'    => 'PPPK Golongan IX',
                'jabatan'    => 'Operator Layanan Operasional',
                'department' => 'Subbagian Umum & Keuangan',
            ],
            [
                'name'       => 'Lia Anggraini, S.Kom.',
                'slug'       => 'lia-anggraini',
                'nip'        => '199703122023212011',
                'email'      => 'lia.anggraini@balmon.go.id',
                'phone'      => '081234567811',
                'pangkat'    => 'PPPK Golongan IX',
                'jabatan'    => 'Penata Layanan Operasional',
                'department' => 'Tata Usaha dan Rumah Tangga',
            ],
            [
                'name'       => 'M. Rizky Pratama, A.Md.',
                'slug'       => 'm-rizky-pratama',
                'nip'        => '199806152023211012',
                'email'      => 'rizky.pratama@balmon.go.id',
                'phone'      => '081234567812',
                'pangkat'    => 'PPPK Golongan VII',
                'jabatan'    => 'Operator Layanan Operasional',
                'department' => 'Tim Sarana dan Prasarana',
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
                    'pangkat'    => $pegawai['pangkat'],
                    'jabatan'    => $pegawai['jabatan'],
                    'department' => $pegawai['department'],
                ]
            );
        }
    }
}