<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Permohonan Peminjaman Kendaraan Dinas</title>
    <style>
        /* 1. Pengaturan Halaman PDF (Ukuran Margin Kertas A4) */
        @page { margin: 1.5cm 2cm 1.5cm 2cm; }

        /* 2. Pengaturan Font Dasar Seluruh Dokumen (Times New Roman) */
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.35; color: #000; }

        /* 3. Pengaturan Tabel Kop Surat */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .header-table td { vertical-align: middle; }

        /* 4. Pengaturan Area Logo Komdigi (Kolom Kiri 15%) */
        .logo-container { width: 20%; text-align: left; }
        .logo-img { height: 90px; width: auto; }

        /* 5. Pengaturan Teks Instansi Kop Surat (Kolom Kanan 85%) & Hirarki Ukuran */
        .header-text { width: 80%; text-align: left; padding-left: 5px; }
        .header-text h3 { font-size: 12pt; margin: 0; font-weight: bold; color: #000; text-transform: uppercase; line-height: 1.2; }
        .header-text h2 { font-size: 13.5pt; margin: 0; font-weight: bold; color: #000; text-transform: uppercase; line-height: 1.2; }
        .header-text h1 { font-size: 16pt; margin: 0; font-weight: bold; color: #000; text-transform: uppercase; line-height: 1.2; }
        .header-text p { font-size: 6pt; margin: 3px 0 0 0; color: #000; font-weight: normal; line-height: 1.2; }

        /* 6. Garis Hitam Pembatas Kop Surat */
        .header-line { border: none; border-top: 2.5px solid #000; margin-top: 2px; margin-bottom: 18px; }

        /* 7. Judul Dokumen (Misal: FORMULIR PERMOHONAN / BAST) */
        .title { text-align: center; font-weight: bold; font-size: 11pt; text-transform: uppercase; margin-top: 10px; margin-bottom: 20px; }

        /* 8. Tabel Isian Data (Nama, NIP, Merek Kendaraan, dll) */
        .content-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .content-table td { padding: 3px 0; vertical-align: top; }
        .content-table td.label { width: 32%; }
        .content-table td.colon { width: 3%; text-align: center; }
        .content-table td.value { width: 65%; font-weight: bold; }

        /* 9. Paragraf Pernyataan / Isi Pasal */
        .paragraph { text-align: justify; text-indent: 30px; margin-bottom: 12px; }

        /* 10. Tabel Area Tanda Tangan */
        .ttd-table { width: 100%; margin-top: 25px; border-collapse: collapse; }
        .ttd-table td { width: 50%; text-align: center; vertical-align: top; }
    </style>
</head>
<body>

    {{-- KOP SURAT BALMON SEMARANG --}}
    <table class="header-table">
        <tr>
            <td class="logo-container">
                @if(file_exists(public_path('images/logo-komdigi.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-komdigi.png'))) }}" class="logo-img">
                @else
                    <strong style="font-size: 13pt; font-weight: bold;">KOMDIGI</strong>
                @endif
            </td>
            <td class="header-text">
                <h1>KEMENTERIAN KOMUNIKASI DAN DIGITAL RI</h1>
                <h2>DIREKTORAT JENDERAL INFRASTRUKTUR DIGITAL</h2>
                <h3>BALAI MONITOR SPEKTRUM FREKUENSI RADIO DAN INFRASTRUKTUR DIGITAL KELAS I SEMARANG</h3>
                <p>Komplek Semarang Indah Blok C-III/1-3, Semarang, 50144, Telp. (024) 7608017, WA: 0877 7000 0157 / 0899 3333 5757, tu.upt_semarang@komdigi.go.id</p>
            </td>
        </tr>
    </table>
    <hr class="header-line">

    <div class="title">
        FORMULIR PERMOHONAN PEMINJAMAN KENDARAAN<br>
        DINAS OPERASIONAL/FUNGSIONAL<br>
        BALAI MONITOR SPEKTRUM FREKUENSI RADIO DAN INFRASTRUKTUR DIGITAL<br>
        KELAS I SEMARANG
    </div>

    <p>Saya yang bertanda tangan di bawah ini:</p>

    <table class="content-table" style="margin-left: 20px;">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td class="value">{{ $booking->user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIP</td>
            <td class="colon">:</td>
            <td class="value">{{ $booking->user->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Pangkat/Golongan</td>
            <td class="colon">:</td>
            <td class="value">{{ $booking->user->pangkat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td class="colon">:</td>
            <td class="value">{{ $booking->user->jabatan ?? '-' }}</td>
        </tr>
    </table>

    <p>Menyatakan akan meminjam kendaraan dinas operasional/fungsional berupa:</p>

    <table class="content-table" style="margin-left: 20px;">
        <tr>
            <td class="label">Merek/Tipe Kendaraan</td>
            <td class="colon">:</td>
            <td class="value">
                {{ $booking->vehicle ? ($booking->vehicle->brand . ' ' . $booking->vehicle->model) : 'Armada Operasional (Belum Di-Plot Admin)' }}
            </td>
        </tr>
        <tr>
            <td class="label">Nomor Polisi Kendaraan</td>
            <td class="colon">:</td>
            <td class="value">{{ $booking->vehicle->plate_number ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Durasi Peminjaman</td>
            <td class="colon">:</td>
            <td class="value">
                {{ $booking->start_date->isoFormat('D MMMM YYYY') }} s.d. {{ $booking->end_date->isoFormat('D MMMM YYYY') }} 
                ({{ ceil($booking->start_date->diffInDays($booking->end_date))+1 ?: 1 }} Hari)
            </td>
        </tr>
        <tr>
            <td class="label">Tanggal Pengembalian</td>
            <td class="colon">:</td>
            <td class="value">
                {{ $booking->end_date->isoFormat('dddd, D MMMM YYYY') }}
            </td>
        </tr>
        <tr>
            <td class="label">Untuk Keperluan</td>
            <td class="colon">:</td>
            <td class="value">{{ $booking->purpose }} (Tujuan: {{ $booking->destination }})</td>
        </tr>
    </table>

    <p class="paragraph">
        Atas peminjaman kendaraan dinas operasional/fungsional tersebut saya berjanji untuk memakai dengan baik selama saya pinjam, serta sanggup untuk mengembalikan dalam keadaan baik selama masa peminjaman.
    </p>

    <p class="paragraph">
        Apabila selama masa peminjaman terjadi kerusakan dan/atau kehilangan serta hal lain yang ditimbulkan, maka saya bertanggung jawab sepenuhnya sesuai ketentuan.
    </p>

    <table class="ttd-table">
        <tr>
            <td></td>
            <td>Semarang, {{ $booking->created_at->isoFormat('D MMMM YYYY') }}<br>Peminjam,</td>
        </tr>
        <tr>
            <td style="height: 100px; text-align: center; vertical-align: middle;">
                {{-- Spasium kosong jika belum ada QR Code --}}
            </td>
            <td style="height: 100px; text-align: center; vertical-align: middle;">
                {{-- TAMPILKAN QR CODE TTE JIKA VEHICLE_ID TERISI --}}
                @if($booking->vehicle_id && isset($qrCode))
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="TTE QR Code" style="width: 100px; height: 100px; margin-top: 5px; margin-bottom: 5px;">
                @endif
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <strong><u>{{ $booking->user->name ?? '-' }}</u></strong><br>
                NIP. {{ $booking->user->nip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>