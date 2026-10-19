<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST Peminjaman Kendaraan Dinas</title>
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
        BERITA ACARA SERAH TERIMA PEMINJAMAN KENDARAAN DINAS OPERASIONAL/FUNGSIONAL<br>
        <span style="font-weight: normal; font-size: 9pt;">Nomor: BAST/{{ $booking->letter_number }}</span>
    </div>

    <p>Pada hari ini, <strong>{{ $booking->start_date->isoFormat('dddd') }}</strong>, tanggal <strong>{{ $booking->start_date->isoFormat('D MMMM YYYY') }}</strong>, bertempat di Balai Monitor Spektrum Frekuensi Radio dan Infrastruktur Digital Kelas I Semarang, kami yang bertanda tangan di bawah ini:</p>

    <table class="content-table">
        {{-- PIHAK PERTAMA (PENANGGUNG JAWAB/PENGELOLA KENDARAAN) --}}
        <tr>
            <td style="width: 3%; vertical-align: top;">1.</td>
            <td style="width: 25%;">Nama</td>
            <td style="width: 3%;">:</td>
            <td><strong>{{ $booking->approver->name ?? 'Penanggung Jawab BMN' }}</strong></td>
        </tr>
        <tr>
            <td></td>
            <td>NIP</td>
            <td>:</td>
            <td>{{ $booking->approver->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Pangkat/Golongan</td>
            <td>:</td>
            <td>{{ $booking->approver->pangkat ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td>:</td>
            <td>{{ $booking->approver->jabatan ?? 'Pengelola BMN' }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="3" style="padding-top: 4px; padding-bottom: 10px; text-align: justify;">
                Dalam hal ini bertindak untuk dan atas nama Balai Monitor Spektrum Frekuensi Radio dan Infrastruktur Digital Kelas I Semarang selaku penanggung jawab/pengelola kendaraan dinas operasional/fungsional, selanjutnya disebut <strong>Pihak Pertama</strong>.
            </td>
        </tr>

        {{-- PIHAK KEDUA (PEMINJAM KENDARAAN) --}}
        <tr>
            <td style="vertical-align: top;">2.</td>
            <td>Nama</td>
            <td>:</td>
            <td><strong>{{ $booking->user->name }}</strong></td>
        </tr>
        <tr>
            <td></td>
            <td>NIP</td>
            <td>:</td>
            <td>{{ $booking->user->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Pangkat/Golongan</td>
            <td>:</td>
            <td>{{ $booking->user->pangkat ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td>:</td>
            <td>{{ $booking->user->jabatan ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="3" style="padding-top: 4px; padding-bottom: 4px; text-align: justify;">
                Dalam hal ini bertindak selaku peminjam kendaraan dinas operasional/fungsional, selanjutnya disebut <strong>Pihak Kedua</strong>.
            </td>
        </tr>
    </table>

    <p>Pihak Pertama dan Pihak Kedua selanjutnya secara masing–masing disebut sebagai “Pihak” dan bersama-sama disebut sebagai “Para Pihak”.</p>

    {{--==================== PASAL 1 ====================--}}
    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 10px;">
        PASAL 1<br>
        DEFINISI
    </div>
    <p style="text-align: justify; margin-bottom: 8px;">
        Dalam berita acara serah terima peminjaman kendaraan dinas operasional/fungsional (BAST Peminjaman) ini, kata-kata berikut mempunyai arti sebagai berikut:
    </p>
    <ol style="margin-top: 4px; margin-bottom: 10px; padding-left: 20px; text-align: justify;">
        <li style="margin-bottom: 6px;">
            <strong>Kendaraan Dinas Operasional</strong> merupakan kendaraan bermotor selain Kendaraan Dinas Operasional Jabatan.
        </li>
        <li style="margin-bottom: 6px;">
            <strong>Kendaraan Dinas Fungsional</strong> merupakan kendaraan bermotor yang digunakan untuk mendukung tugas dan fungsi tertentu Kementerian.
        </li>
        <li style="margin-bottom: 6px;">
            <strong>Penanggung Jawab Kendaraan</strong> merupakan pejabat/pegawai minimal pejabat pengawas/yang disetarakan yang ditunjuk oleh Kuasa Pengguna Barang (KPB) yang berwenang dalam hal pengamanan kendaraan dinas yang ada pada satker tersebut.
        </li>
        <li style="margin-bottom: 6px;">
            <strong>Peminjam Kendaraan</strong> merupakan pejabat/pegawai yang menggunakan kendaraan dinas dan telah memiliki BAST Peminjaman, sehingga pengamanan kendaraan dinas dalam durasi peminjaman sepenuhnya menjadi tanggung jawab peminjam.
        </li>
    </ol>

    {{--==================== PASAL 2 ====================--}}
    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 10px;">
        PASAL 2<br>
        RUANG LINGKUP
    </div>
    <p style="text-align: justify; margin-bottom: 12px;">
        Ruang lingkup perjanjian dalam BAST Peminjaman ini meliputi objek peminjaman, hak, kewajiban, dan larangan bagi Para Pihak serta kondisi tertentu yang mempengaruhi.
    </p>

    {{--==================== PASAL 3 ====================--}}
    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 10px;">
        PASAL 3<br>
        OBJEK PEMINJAMAN
    </div>
    <ol style="margin-top: 4px; margin-bottom: 10px; padding-left: 20px; text-align: justify;">
        <li style="margin-bottom: 6px;">
            Pihak Pertama menyerahkan dan Pihak Kedua menerima satu unit kendaraan dinas operasional/fungsional dengan rincian:
            <table class="content-table" style="margin-top: 3px; margin-bottom: 4px; margin-left: 5px;">
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
                    <td>c. Durasi Peminjaman</td>
                    <td style="text-align: center;">:</td>
                    <td>
                        {{ $booking->start_date->isoFormat('D MMMM YYYY') }} s.d. {{ $booking->end_date->isoFormat('D MMMM YYYY') }} 
                        ({{ ceil($booking->start_date->diffInDays($booking->end_date))+1 ?: 1 }} hari)
                    </td>
                </tr>
                <tr>
                    <td>d. Rencana Pengembalian</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $booking->end_date->isoFormat('dddd, D MMMM YYYY') }}</td>
                </tr>
                <tr>
                    <td>e. Untuk Keperluan</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $booking->purpose }}<br>(Tujuan: {{ $booking->destination }})</td>
                </tr>
            </table>
        </li>
        <li style="margin-bottom: 6px;">
            Kendaraan dinas operasional/fungsional diserahkan dalam kondisi baik dan lengkap, dengan kelengkapannya meliputi:
            <ol type="a" style="margin-top: 2px; margin-bottom: 2px; padding-left: 20px;">
                <li>STNK;</li>
                <li>Kunci kendaraan; dan</li>
                <li>Kelengkapan kendaraan lainnya (Kartu BBM/RFID jika ada).</li>
            </ol>
            <p style="margin-top: 4px; margin-bottom: 0;">
                Penanggung Jawab berhak untuk tidak menyerahkan Kelengkapan kendaraan lainnya jika dinilai tidak diperlukan.
            </p>
        </li>
    </ol>

    {{--==================== PASAL 4 ====================--}}
    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 10px;">
        PASAL 4<br>
        JANGKA WAKTU PEMINJAMAN
    </div>

    <ol style="margin-top: 4px; margin-bottom: 10px; padding-left: 20px; text-align: justify;">
        <li style="margin-bottom: 6px;">
            Peminjaman ini akan berlaku sejak ditandatanganinya BAST Peminjaman ini sampai dengan durasi peminjaman berakhir sebagaimana tercantum dalam pasal 3.1 BAST Peminjaman ini dan/atau ketika Pihak Pertama akan menggunakan kendaraan dinas operasional/fungsional untuk mendukung tugas dan fungsi Pihak Pertama.
        </li>
        <li style="margin-bottom: 6px;">
            BAST Peminjaman ini dapat diperpanjang ketika Pihak Pertama telah menyetujui Formulir Permohonan Perpanjangan Peminjaman Kendaraan Dinas Operasional/Fungsional kepada Pihak Kedua dan dituangkan dalam Formulir Perpanjangan Peminjaman Kendaraan Dinas Operasional/Fungsional.
        </li>
        <li style="margin-bottom: 6px;">
            Masa peminjaman berlaku sejak tanggal mulai sampai dengan tanggal pengembalian sebagaimana tercantum pada pasal 3.1 Penggunaan kendaraan, di luar jangka waktu tersebut tidak diperkenankan tanpa persetujuan tertulis pejabat berwenang.
        </li>
    </ol>

    {{--==================== PASAL 5 ====================--}}
    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 10px;">
        PASAL 5<br>
        HAK DAN KEWAJIBAN PARA PIHAK
    </div>

    <ol style="margin-top: 4px; margin-bottom: 10px; padding-left: 20px; text-align: justify;">
        <li style="margin-bottom: 8px;">
            Hak dan kewajiban Pihak Pertama berdasarkan BAST Peminjaman ini adalah sebagai berikut:
            <ol type="a" style="margin-top: 4px; padding-left: 20px;">
                <li style="margin-bottom: 4px;">
                    Pihak Pertama wajib menyerahkan kendaraan dinas operasional/fungsional beserta kelengkapannya sebagaimana tertulis pada pasal 3.2 BAST Peminjaman ini.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Pertama wajib untuk memberitahukan kepada Pihak Kedua segala sesuatu terkait dengan kondisi Kendaraan selambat-lambatnya pada saat penandatanganan BAST Peminjaman ini.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Pertama berhak meminta kembali kendaraan dinas operasional/fungsional yang sedang dipinjam Pihak Kedua sebelum masa peminjaman selesai sesuai dengan kebutuhan tugas dan fungsi Pihak Pertama.
                </li>
                <li style="margin-bottom: 4px;">
                    Apabila di kemudian hari timbul biaya akibat kerusakan kendaraan (klaim asuransi) selama periode peminjaman Pihak Kedua, Pihak Pertama berhak untuk meminta biaya penggantian yang ditimbulkan kepada Pihak Kedua.
                </li>
                <li style="margin-bottom: 4px;">
                    Apabila di kemudian hari timbul biaya akibat adanya pelanggaran lalu lintas selama periode peminjaman Pihak Kedua, Pihak Pertama berhak untuk meminta biaya penggantian yang ditimbulkan kepada Pihak Kedua.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Pertama wajib melakukan verifikasi kondisi kendaraan beserta kelengkapannya saat Pihak Kedua mengembalikan kendaraan dinas operasional/fungsional. Dalam hal verifikasi dinyatakan tidak lengkap, Pihak Pertama berhak untuk meminta pertanggungjawaban Pihak Kedua.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Pertama berhak menyetujui atau menolak Formulir Permohonan Peminjaman Kendaraan Dinas Operasional/Fungsional dan Formulir Perpanjangan Peminjaman Kendaraan Dinas Operasional/Fungsional yang diajukan oleh Pihak Kedua.
                </li>
            </ol>
        </li>

        <li style="margin-bottom: 8px;">
            Hak dan kewajiban Pihak Kedua berdasarkan BAST Peminjaman ini adalah sebagai berikut:
            <ol type="a" style="margin-top: 4px; padding-left: 20px;">
                <li style="margin-bottom: 4px;">
                    Pihak Kedua berhak menggunakan kendaraan dinas operasional/fungsional sesuai keperluan yang disetujui.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Kedua wajib membuat Formulir Permohonan Peminjaman Kendaraan Dinas Operasional/Fungsional sebelum meminjam kendaraan dinas operasional/fungsional.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Kedua wajib mengembalikan kendaraan dinas operasional/fungsional ketika masa peminjaman berakhir dan/atau ketika Pihak Pertama akan menggunakan kendaraan dinas operasional/fungsional untuk kebutuhan tugas dan fungsi Pihak Pertama.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Kedua Wajib menjaga, merawat, dan mengembalikan kendaraan tepat waktu dan dalam kondisi baik.
                </li>
                <li style="margin-bottom: 4px;">
                    Dalam hal tidak tersedianya ruang parkir di lingkungan Kementerian Komunikasi dan Digital maka Pihak Kedua dapat menyimpan kendaraan dinas operasional/fungsional di lokasi lain di luar lingkungan kantor dengan tetap memperhatikan pengamanan fisik kendaraan.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Kedua wajib mengembalikan kendaraan lebih awal apabila sudah tidak digunakan.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Kedua wajib membayar biaya yang timbul dan/atau akan timbul akibat kerusakan kendaraan (klaim asuransi) selama periode peminjaman.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Kedua wajib membayar biaya yang timbul dan/atau akan timbul akibat adanya pelanggaran lalu lintas selama periode peminjaman.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Kedua wajib bertanggung jawab sepenuhnya sesuai ketentuan apabila terjadi kerusakan dan/atau kehilangan kelengkapan kendaraan sebagaimana tersebut pada pasal 3.2 serta hal lain yang ditimbulkan selama periode peminjaman.
                </li>
                <li style="margin-bottom: 4px;">
                    Pihak Kedua berhak mengajukan Formulir Perpanjangan Peminjaman Kendaraan Dinas Operasional/Fungsional sebelum berakhirnya masa peminjaman.
                </li>
            </ol>
        </li>
    </ol>

    {{--==================== PASAL 6 ====================--}}
    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 10px;">
        PASAL 6<br>
        LARANGAN
    </div>
    <ol style="margin-top: 4px; margin-bottom: 10px; padding-left: 20px; text-align: justify;">
        <li style="margin-bottom: 6px;">
            Dalam hal penggunaan kartu BBM, Pihak Kedua dilarang:
            <ol type="a" style="margin-top: 4px; padding-left: 20px;">
                <li style="margin-bottom: 4px;">
                    Menggunakan di luar hari kerja kecuali mendapatkan persetujuan dari pimpinan atau Pihak Pertama;
                </li>
                <li style="margin-bottom: 4px;">
                    Menggunakan pada saat melaksanakan perjalanan dinas luar kota.
                </li>
            </ol>
        </li>
        <li style="margin-bottom: 6px;">
            Pada masa peminjaman, Pihak Kedua dilarang memindahtangankan kendaraan dinas operasional/fungsional dan/atau kelengkapannya sebagaimana disebutkan pada pasal 3.2 kepada pihak di luar BAST Peminjaman ini.
        </li>
    </ol>

    {{--==================== PASAL 7 ====================--}}
    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 10px;">
        PASAL 7<br>
        KERUSAKAN DAN KEHILANGAN
    </div>
    <ol style="margin-top: 4px; margin-bottom: 10px; padding-left: 20px; text-align: justify;">
        <li style="margin-bottom: 6px;">
            Kerusakan kendaraan dinas operasional/fungsional pada prinsipnya merupakan tanggung jawab Pihak Kedua, kecuali kendaraan berada di lokasi lingkungan kantor Kementerian Komunikasi dan Digital.
        </li>
        <li style="margin-bottom: 6px;">
            Kehilangan kendaraan dinas operasional/fungsional akan diproses sesuai dengan ketentuan.
        </li>
    </ol>

    {{--==================== PASAL 8 ====================--}}
    <div style="text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 10px;">
        PASAL 8<br>
        KEADAAN KAHAR
    </div>
    <ol style="margin-top: 4px; margin-bottom: 10px; padding-left: 20px; text-align: justify;">
        <li style="margin-bottom: 6px;">
            Keadaan Kahar adalah peristiwa-peristiwa yang berada di luar kekuasaan dan kendali Para Pihak, telah dinyatakan resmi oleh pihak berwenang (instansi terkait), bukan merupakan akibat dari kelalaian Para Pihak dalam BAST Peminjaman ini dan yang dapat berdampak kepada kelangsungan BAST Peminjaman ini. Peristiwa-peristiwa yang termasuk dalam kategori Keadaan Kahar, antara lain:
            <ol type="a" style="margin-top: 4px; padding-left: 20px;">
                <li style="margin-bottom: 4px;">
                    Bencana alam (gempa bumi, tanah longsor/tanah bergerak, banjir, tsunami, serta letusan gunung berapi) dan bencana alam lainnya yang dinyatakan secara resmi oleh instansi terkait; atau
                </li>
                <li style="margin-bottom: 4px;">
                    Perang, kerusuhan, dan gangguan sistem kendaraan dalam skala nasional maupun di seluruh dunia sehingga mengakibatkan tidak dapat berlanjutnya peminjaman kendaraan dinas operasional/fungsional.
                </li>
            </ol>
        </li>
        <li style="margin-bottom: 6px;">
            Dalam hal terjadi Keadaan Kahar sebagaimana dimaksud pada ayat 1 Pasal ini maka pihak yang terkena dampak Keadaan Kahar wajib memberitahukan kepada Pihak lainnya paling lambat 3 (tiga) hari kalender sejak tanggal terjadinya Keadaan Kahar.
        </li>
        <li style="margin-bottom: 6px;">
            Apabila terjadi kerusakan kendaraan dinas operasional/fungsional Keadaan Kahar maka akan diproses sesuai ketentuan.
        </li>
    </ol>

    <p style="text-align: justify; margin-top: 15px; margin-bottom: 20px;">
        Demikian Berita Acara Serah Terima ini dibuat sebagai dasar peminjaman dan pengendalian terhadap penggunaan kendaraan dinas operasional/fungsional untuk digunakan sebagaimana mestinya.
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