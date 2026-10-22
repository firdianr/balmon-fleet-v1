<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; max-width: 600px; }
        .header { background-color: #1e3a8a; color: white; padding: 15px; border-radius: 6px 6px 0 0; text-align: center; }
        .content { padding: 20px 0; }
        .table-info { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-info td { padding: 8px; border-bottom: 1px solid #f3f4f6; }
        .footer { margin-top: 20px; font-size: 12px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0;">Balmon Fleet Semarang</h2>
        </div>
        <div class="content">
            <p>Halo, <strong>{{ $booking->user->name }}</strong>,</p>
            <p>Permohonan peminjaman armada kendaraan Anda telah <strong>berhasil diajukan</strong> dan saat ini sedang dalam proses peninjauan.</p>
            
            <table class="table-info">
                <tr>
                    <td><strong>Nomor Surat Tugas:</strong></td>
                    <td>{{ $booking->letter_number }}</td>
                </tr>
                <tr>
                    <td><strong>Tujuan:</strong></td>
                    <td>{{ $booking->destination }}</td>
                </tr>
                <tr>
                    <td><strong>Maksud Kegiatan:</strong></td>
                    <td>{{ $booking->purpose }}</td>
                </tr>
                <tr>
                    <td><strong>Tanggal Pelaksanaan:</strong></td>
                    <td>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</td>
                </tr>
            </table>

            <p style="margin-top:20px;">
                Berkas <strong>Formulir Permohonan Peminjaman (PDF)</strong> telah dilampirkan pada email ini untuk disimpan sebagai arsip Anda.
            </p>
        </div>
        <div class="footer">
            <p>Email ini dikirim secara otomatis oleh Sistem Informasi Balmon Fleet Semarang.</p>
        </div>
    </div>
</body>
</html>