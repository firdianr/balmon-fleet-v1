<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST Pengembalian Kendaraan Dinas</title>
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

   <div class="title" style="text-align: center; font-weight: bold; font-size: 11pt; text-transform: uppercase; margin-top: 10px; margin-bottom: 15px;">
        <u>BERITA ACARA SERAH TERIMA PENGEMBALIAN KENDARAAN DINAS OPERASIONAL/FUNGSIONAL</u><br>
        <span style="font-weight: normal; font-size: 10pt; text-transform: none; display: inline-block; margin-top: 4px;">
            Nomor: BAST-KMB/{{ $booking->letter_number }}
        </span>
    </div>

    @php
        $retDate = $booking->log && $booking->log->checked_in_at ? \Carbon\Carbon::parse($booking->log->checked_in_at) : $booking->end_date;
    @endphp

    <p style="text-align: justify; margin-bottom: 10px;">
        Pada hari ini, <strong>{{ $retDate->isoFormat('dddd') }}</strong>, tanggal <strong>{{ $retDate->isoFormat('D') }}</strong> bulan <strong>{{ $retDate->isoFormat('MMMM') }}</strong> tahun <strong>{{ $retDate->isoFormat('YYYY') }}</strong>, bertempat di Balai Monitor Spektrum Frekuensi Radio dan Infrastruktur Digital Kelas I Semarang, kami yang bertanda tangan di bawah ini:
    </p>

    {{-- TABEL IDENTITAS PARA PIHAK --}}
    <table class="content-table" style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        {{-- PIHAK PERTAMA --}}
        <tr>
            <td style="width: 3%; vertical-align: top;">1.</td>
            <td style="width: 25%;">Nama</td>
            <td style="width: 3%; text-align: center;">:</td>
            <td><strong>{{ $booking->returnApprover->name ?? $booking->approver->name ?? 'Penanggung Jawab BMN' }}</strong></td>
        </tr>
        <tr>
            <td></td>
            <td>NIP</td>
            <td style="text-align: center;">:</td>
            <td>{{ $booking->returnApprover->nip ?? $booking->approver->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Pangkat/Golongan</td>
            <td style="text-align: center;">:</td>
            <td>{{ $booking->returnApprover->pangkat ?? $booking->approver->pangkat ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td style="text-align: center;">:</td>
            <td>{{ $booking->returnApprover->jabatan ?? $booking->approver->jabatan ?? 'Pengelola BMN' }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="3" style="padding-top: 4px; padding-bottom: 10px; text-align: justify;">
                Dalam hal ini bertindak untuk dan atas nama Balai Monitor Spektrum Frekuensi Radio dan Infrastruktur Digital Kelas I Semarang selaku penanggung jawab/pengelola kendaraan dinas operasional/fungsional, selanjutnya disebut <strong>Pihak Pertama</strong>.
            </td>
        </tr>

        {{-- PIHAK KEDUA --}}
        <tr>
            <td style="vertical-align: top;">2.</td>
            <td>Nama</td>
            <td style="text-align: center;">:</td>
            <td><strong>{{ $booking->user->name }}</strong></td>
        </tr>
        <tr>
            <td></td>
            <td>NIP</td>
            <td style="text-align: center;">:</td>
            <td>{{ $booking->user->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Pangkat/Golongan</td>
            <td style="text-align: center;">:</td>
            <td>{{ $booking->user->pangkat ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td style="text-align: center;">:</td>
            <td>{{ $booking->user->jabatan ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="3" style="padding-top: 4px; padding-bottom: 10px; text-align: justify;">
                Dalam hal ini bertindak selaku peminjam kendaraan dinas operasional/fungsional, selanjutnya disebut <strong>Pihak Kedua</strong>.
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 12px;">
        PERNYATAAN SERAH TERIMA
    </div>

    <ol style="margin-top: 4px; margin-bottom: 10px; padding-left: 20px; text-align: justify;">
        <li style="margin-bottom: 8px;">
            Pihak Kedua telah mengembalikan dan Pihak Pertama telah menerima satu unit kendaraan dinas operasional dengan rincian:
            <table class="content-table" style="margin-top: 4px; margin-bottom: 4px; margin-left: 5px;">
                <tr>
                    <td style="width: 32%;">a. Merek/Tipe Kendaraan</td>
                    <td style="width: 3%; text-align: center;">:</td>
                    <td><strong>{{ $booking->vehicle->brand ?? '-' }} {{ $booking->vehicle->model ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td>b. Nomor Polisi Kendaraan</td>
                    <td style="text-align: center;">:</td>
                    <td><strong>{{ $booking->vehicle->plate_number ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td>c. Tanggal Pengembalian</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $retDate->isoFormat('dddd, D MMMM YYYY [pukul] HH.mm') }} WIB</td>
                </tr>
                <tr>
                    <td>d. Nomor BAST Peminjaman</td>
                    <td style="text-align: center;">:</td>
                    <td>BAST/{{ $booking->letter_slug }}/{{ $booking->created_at->format('Y') }}</td>
                </tr>
            </table>
        </li>
        <li style="margin-bottom: 8px;">
            Kendaraan dinas operasional/fungsional diserahkan dalam kondisi baik dan lengkap, dengan kelengkapannya meliputi:
            <ol type="a" style="margin-top: 4px; padding-left: 20px;">
                <li style="margin-bottom: 2px;">STNK;</li>
                <li style="margin-bottom: 2px;">Kunci;</li>
                <li style="margin-bottom: 2px;">Kartu BBM (RFID atau sejenisnya jika ada); dan</li>
                <li style="margin-bottom: 2px;">Kelengkapan kendaraan lainnya (jika ada).</li>
            </ol>
            {{-- @if($booking->log && $booking->log->condition_notes)
                <div style="margin-top: 6px; font-style: italic; color: #444;">
                    Catatan Kondisi Pengembalian: "{{ $booking->log->condition_notes }}"
                </div>
            @endif --}}
        </li>
    </ol>

    <br><br>
    <p style="text-align: justify; margin-top: 15px; margin-bottom: 25px;">
        Demikian Berita Acara Serah Terima ini dibuat sebagai dasar pengembalian dan pengendalian terhadap penggunaan kendaraan dinas operasional/fungsional untuk digunakan sebagaimana mestinya.
    </p>

    <table class="ttd-table">
        <tr>
            <td>PIHAK PERTAMA<br>Penanggung Jawab,</td>
            <td>PIHAK KEDUA<br>Peminjam,</td>
        </tr>
        <tr>
            <td style="height: 60px;"></td>
            <td></td>
        </tr>
        <tr>
            <td>
                <strong><u>{{ $booking->approver->name ?? 'Penanggung Jawab BMN' }}</u></strong><br>
                NIP. {{ $booking->approver->nip ?? '-' }}
            </td>
            <td>
                <strong><u>{{ $booking->user->name }}</u></strong><br>
                NIP. {{ $booking->user->nip ?? '-' }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 12px; text-align: center;">
                <strong>Mengetahui,</strong><br>
                Kuasa Pengguna Barang/Pimpinan Unit Akuntansi<br>
                Pembantu Kuasa Pengguna Barang,
                <br><br><br><br><br>
                <strong><u>( Nama Pejabat )</u></strong><br>
                NIP. (NIP Pejabat)
            </td>
        </tr>
    </table>

</body>
</html>