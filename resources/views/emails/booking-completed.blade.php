<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman Armada Selesai</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 0; background-color: #f3f4f6; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f3f4f6; padding: 24px 0; }
        .container { max-width: 640px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #1e1b4b; color: #ffffff; padding: 24px; text-align: center; }
        .header h2 { margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 0.5px; }
        .header p { margin: 4px 0 0 0; font-size: 12px; color: #c7d2fe; }
        .content { padding: 24px; }
        .badge-success { display: inline-block; background-color: #dcfce7; color: #15803d; font-weight: bold; font-size: 11px; padding: 4px 12px; border-radius: 9999px; text-transform: uppercase; margin-bottom: 16px; border: 1px solid #bbf7d0; }
        
        .section-title { font-size: 14px; font-weight: bold; color: #1e1b4b; margin: 20px 0 8px 0; padding-bottom: 4px; border-b: 2px solid #e0e7ff; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 13px; }
        .table-info td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .table-info td.label { font-weight: bold; color: #4b5563; width: 38%; }
        .table-info td.value { color: #111827; }

        .attachments-box { background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 14px; margin-top: 20px; }
        .attachments-title { font-size: 12px; font-weight: bold; color: #334155; margin-bottom: 8px; display: block; }
        .attachments-list { margin: 0; padding-left: 18px; font-size: 12px; color: #475569; }
        .attachments-list li { margin-bottom: 4px; }

        .footer { background-color: #f9fafb; padding: 16px; font-size: 11px; color: #6b7280; text-align: center; border-top: 1px solid #f3f4f6; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- HEADER PROFIL ORGANISASI -->
            <div class="header">
                <h2>BALMON FLEET SEMARANG</h2>
                <p>Balai Monitor Spektrum Frekuensi Radio Kelas I Semarang</p>
            </div>
            
            <div class="content">
                <span class="badge-success">✓ Process Completed & Verified</span>
                
                <p style="margin-top:0;">Halo, <strong>{{ $recipientName }}</strong>,</p>
                <p>Seluruh tahapan peminjaman dan pengembalian armada operasional dinas untuk permohonan atas nama <strong>{{ $booking->user->name }}</strong> telah <strong>diterima oleh Kuasa Pengguna Barang/Pimpinan Unit Akuntansi Pembantu Kuasa Pengguna Barang</strong>. Status transaksi peminjaman dinyatakan <strong>SELESAI (COMPLETED)</strong>.</p>
                
                <!-- SEKSI 1: RINCIAN PERMOHONAN DINAS -->
                <div class="section-title">📋 1. Informasi Permohonan & Perjalanan</div>
                <table class="table-info">
                    @if($booking->letter_type === 'nota_dinas')
                        {{-- TAMPILKAN KEDUANYA UNTUK NOTA DINAS --}}
                        <tr>
                            <td class="label">Nomor Nota Dinas:</td>
                            <td class="value"><strong>{{ $booking->letter_number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="label">Nomor Surat Tugas (Susulan):</td>
                            <td class="value"><strong>{{ $booking->return_letter_number ?? '-' }}</strong></td>
                        </tr>
                    @else
                        {{-- HANYA TAMPILKAN SURAT TUGAS --}}
                        <tr>
                            <td class="label">Nomor Surat Tugas:</td>
                            <td class="value"><strong>{{ $booking->letter_number }}</strong></td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Jenis Surat:</td>
                        <td class="value">{{ strtoupper(str_replace('_', ' ', $booking->letter_type)) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Maksud & Tujuan:</td>
                        <td class="value">{{ $booking->purpose }} (📍 {{ $booking->destination }})</td>
                    </tr>
                    <tr>
                        <td class="label">Jadwal Rencana:</td>
                        <td class="value">
                            {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Unit Kendaraan:</td>
                        <td class="value"><strong>{{ $booking->vehicle?->brand }} {{ $booking->vehicle?->model }}</strong> ({{ $booking->vehicle?->plate_number }})</td>
                    </tr>
                    <tr>
                        <td class="label">Pengemudi (Driver):</td>
                        <td class="value">{{ $booking->driver?->name ?? 'Tanpa Driver (Lepas Kunci)' }}</td>
                    </tr>
                </table>

                <!-- SEKSI 2: LOG REALISASI KEPULANGAN & ARMADA -->
                <div class="section-title">🚘 2. Realisasi Pengembalian Garasi</div>
                <table class="table-info">
                    <tr>
                        <td class="label">Waktu Berangkat (Check-out):</td>
                        <td class="value">{{ $booking->log?->checked_out_at ? \Carbon\Carbon::parse($booking->log->checked_out_at)->format('d M Y, H:i') . ' WIB' : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Waktu Kembali (Check-in):</td>
                        <td class="value">{{ $booking->log?->checked_in_at ? \Carbon\Carbon::parse($booking->log->checked_in_at)->format('d M Y, H:i') . ' WIB' : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Kapasitas BBM Akhir:</td>
                        <td class="value"><strong>{{ $booking->log?->end_fuel_level ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Status Kebersihan:</td>
                        <td class="value">
                            @if(optional($booking->endChecklist)->is_washed)
                                <span style="color:#15803d; font-weight:bold;">✓ Sudah Dicuci Bersih</span>
                            @else
                                <span style="color:#b45309;">⚠ Belum / Tidak Dicuci</span>
                            @endif
                        </td>
                    </tr>
                </table>

                <!-- LIST LAMPIRAN PDF -->
                <div class="attachments-box">
                    <span class="attachments-title">📎 Lampiran Dokumen Otomatis (4 File PDF):</span>
                    <ol class="attachments-list">
                        <li><strong>Formulir Permohonan Peminjaman Armada</strong> (PDF)</li>
                        <li><strong>BAST Peminjaman / Serah Terima Kunci</strong> (PDF)</li>
                        <li><strong>Formulir Pengembalian Armada</strong> (PDF)</li>
                        <li><strong>BAST Pengembalian Armada</strong> (PDF)</li>
                    </ol>
                    <p style="margin: 8px 0 0 0; font-size: 11px; color: #64748b;">* Seluruh berkas di atas telah dibubuhkan tanda tangan elektronik (TTD) sah dari pihak terkait.</p>
                </div>
            </div>

            <!-- FOOTER EMAIL -->
            <div class="footer">
                <p style="margin: 0;">Email ini dikirimkan secara otomatis oleh <strong>Sistem Informasi Balmon Fleet Semarang</strong>.</p>
                <p style="margin: 4px 0 0 0;">Harap tidak membalas email ini.</p>
            </div>
        </div>
    </div>
</body>
</html>