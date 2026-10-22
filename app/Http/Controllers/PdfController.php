<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PdfController extends Controller
{
    /**
     * 1. Cetak Formulir Permohonan Peminjaman
     */
    public function printFormulirPermohonan(Booking $booking)
    {
        $booking->load(['user', 'vehicle', 'driver']);

        $qrCode = null;

        // Jika kendaraan sudah di-plot (vehicle_id tidak null), buat QR Code TTE
        if ($booking->status !== 'pending') {
            $tteInfo = "DOKUMEN TERVERIFIKASI TTE BALMON SEMARANG\n"
                . "Jenis Dokumen: Formulir Pengembalian Kendaraan\n"
                . "No. Surat: " . $booking->letter_number . "\n"
                . "Pemohon: " . ($booking->user->name ?? '-') . " (NIP: " . ($booking->user->nip ?? '-') . ")\n"
                . "Tanggal Pengajuan: " . $booking->created_at->isoFormat('D MMMM YYYY') . "\n"
                . "URL Verifikasi: " . route('bookings.show', $booking->letter_slug);

            $qrCode = base64_encode(
                QrCode::format('svg')
                    ->size(100)
                    ->margin(1)
                    ->generate($tteInfo)
            );
        }

        $pdf = Pdf::loadView('pdf.formulir-permohonan', compact('booking', 'qrCode'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Formulir_Permohonan_' . $booking->letter_slug . '.pdf');
    }

    /**
     * 2. Cetak BAST Peminjaman / Serah Terima Kunci
     */
    public function printBastPeminjaman(Booking $booking)
    {
        if (!in_array($booking->status, ['waiting', 'approved', 'on_trip', 'returned', 'checked', 'completed'])) {
            abort(403, 'BAST Peminjaman belum dapat dicetak pada status saat ini.');
        }

        $booking->load(['user', 'vehicle', 'driver', 'allocator', 'approver']);

        $approverHead = User::where('role', 'approver')->first();

        // Data Penandatangan
        $peminjamName        = $booking->user->name ?? '-';
        $peminjamNip         = $booking->user->nip ?? '-';
        $penanggungJawabName = $booking->allocator->name ?? 'Penanggung Jawab BMN';
        $penanggungJawabNip  = $booking->allocator->nip ?? '-';
        $approverName        = $booking->approver->name ?? ($approverHead->name ?? '( Nama Pejabat )');
        $approverNip         = $booking->approver->nip ?? ($approverHead->nip ?? '-');

        // Flag ketersediaan TTE berdasarkan status
        $isPenanggungJawabSigned = !is_null($booking->allocated_by) && in_array($booking->status, ['waiting', 'approved', 'on_trip', 'returned', 'checked', 'completed']);
        $isApproverSigned        = in_array($booking->status, ['approved', 'on_trip', 'returned', 'checked', 'completed']);

        // 1. QR Code Peminjam (Selalu ada)
        $ttePeminjamInfo = "DOKUMEN TERVERIFIKASI TTE BALMON SEMARANG\n"
            . "Jenis Dokumen: BAST Peminjaman Kendaraan (Peminjam)\n"
            . "No. Surat: " . $booking->letter_number . "\n"
            . "Penandatangan: " . $peminjamName . " (NIP: " . $peminjamNip . ")\n"
            . "Waktu TTD: " . $booking->created_at->isoFormat('D MMMM YYYY HH:mm') . " WIB\n"
            . "URL Verifikasi: " . route('bookings.show', $booking->letter_slug);

        $qrCodePeminjam = base64_encode(QrCode::format('svg')->size(100)->margin(1)->generate($ttePeminjamInfo));

        // 2. QR Code Penanggung Jawab (Aktif di status waiting ke atas)
        $qrCodePenanggungJawab = null;
        if ($isPenanggungJawabSigned) {
            $ttePJInfo = "DOKUMEN TERVERIFIKASI TTE BALMON SEMARANG\n"
                . "Jenis Dokumen: BAST Peminjaman Kendaraan (Penanggung Jawab BMN)\n"
                . "No. Surat: " . $booking->letter_number . "\n"
                . "Penandatangan: " . $penanggungJawabName . " (NIP: " . $penanggungJawabNip . ")\n"
                . "Waktu TTD: " . ($booking->allocated_at ? $booking->allocated_at->isoFormat('D MMMM YYYY HH:mm') : '-') . " WIB\n"
                . "URL Verifikasi: " . route('bookings.show', $booking->letter_slug);

            $qrCodePenanggungJawab = base64_encode(QrCode::format('svg')->size(100)->margin(1)->generate($ttePJInfo));
        }

        // 3. QR Code Approver / Atasan (Aktif di status approved ke atas)
        $qrCodeApprover = null;
        if ($isApproverSigned) {
            $tteApproverInfo = "DOKUMEN TERVERIFIKASI TTE BALMON SEMARANG\n"
                . "Jenis Dokumen: BAST Peminjaman Kendaraan (Atasan / Approver)\n"
                . "No. Surat: " . $booking->letter_number . "\n"
                . "Penandatangan: " . $approverName . " (NIP: " . $approverNip . ")\n"
                . "Waktu TTD: " . ($booking->approved_at ? $booking->approved_at->isoFormat('D MMMM YYYY HH:mm') : '-') . " WIB\n"
                . "URL Verifikasi: " . route('bookings.show', $booking->letter_slug);

            $qrCodeApprover = base64_encode(QrCode::format('svg')->size(100)->margin(1)->generate($tteApproverInfo));
        }

        $tteData = [
            'peminjam' => [
                'signed'    => true,
                'name'      => $peminjamName,
                'nip'       => $peminjamNip,
                'signed_at' => $booking->created_at,
                'qr_code'   => $qrCodePeminjam,
            ],
            'penanggung_jawab' => [
                'signed'    => $isPenanggungJawabSigned,
                'name'      => $penanggungJawabName,
                'nip'       => $penanggungJawabNip,
                'signed_at' => $booking->allocated_at,
                'qr_code'   => $qrCodePenanggungJawab,
            ],
            'approver' => [
                'signed'    => $isApproverSigned,
                'name'      => $approverName,
                'nip'       => $approverNip,
                'signed_at' => $booking->approved_at,
                'qr_code'   => $qrCodeApprover,
            ],
        ];

        $pdf = Pdf::loadView('pdf.bast-peminjaman', compact('booking', 'tteData'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('BAST_Peminjaman_' . $booking->letter_slug . '.pdf');
    }

    /**
     * 3. Cetak Formulir Pengembalian
     */
    public function printFormulirPengembalian(Booking $booking)
    {
        if (!in_array($booking->status, ['returned', 'checked', 'completed'])) {
            abort(403, 'Formulir Pengembalian hanya dapat dicetak setelah kendaraan dikembalikan.');
        }

        $booking->load(['user', 'vehicle', 'driver', 'log']);

        // TTE Info khusus penyelesaian pengembalian
        $tteInfo = "DOKUMEN TERVERIFIKASI TTE BALMON SEMARANG\n"
            . "Jenis Dokumen: Formulir Pengembalian Kendaraan\n"
            . "No. Surat: " . $booking->letter_number . "\n"
            . "Pemohon: " . ($booking->user->name ?? '-') . " (NIP: " . ($booking->user->nip ?? '-') . ")\n"
            . "Tanggal Selesai: " . ($booking->returned_at ? $booking->returned_at->isoFormat('D MMMM YYYY HH:mm') : now()->isoFormat('D MMMM YYYY')) . "\n"
            . "URL Verifikasi: " . route('bookings.show', $booking->letter_slug);

        // Generate QR Code format SVG disandi ke Base64
        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(100)
                ->margin(1)
                ->generate($tteInfo)
        );

        $pdf = Pdf::loadView('pdf.formulir-pengembalian', compact('booking', 'qrCode'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Formulir_Pengembalian_' . $booking->letter_slug . '.pdf');
    }

    /**
     * 4. Cetak BAST Pengembalian
     */
    public function printBastPengembalian(Booking $booking)
    {
        if (!in_array($booking->status, ['returned', 'checked', 'completed'])) {
            abort(403, 'BAST Pengembalian hanya dapat dicetak setelah kendaraan dikembalikan.');
        }

        $booking->load(['user', 'vehicle', 'driver', 'log', 'returnChecker', 'returnApprover']);

        $approverHead = User::where('role', 'approver')->first();

        // Data Penandatangan
        $peminjamName        = $booking->user->name ?? '-';
        $peminjamNip         = $booking->user->nip ?? '-';
        $penanggungJawabName = $booking->returnChecker->name ?? 'Penanggung Jawab BMN';
        $penanggungJawabNip  = $booking->returnChecker->nip ?? '-';
        $approverName        = $booking->returnApprover->name ?? ($approverHead->name ?? '( Nama Pejabat )');
        $approverNip         = $booking->returnApprover->nip ?? ($approverHead->nip ?? '-');

        // Flag ketersediaan TTE berdasarkan status
        $isPenanggungJawabSigned = in_array($booking->status, ['checked', 'completed']);
        $isApproverSigned        = $booking->status === 'completed';

        // 1. QR Code Peminjam (Selalu ada saat pengembalian)
        $peminjamSignedAt = $booking->log?->checked_in_at ?? $booking->updated_at;
        $ttePeminjamInfo  = "DOKUMEN TERVERIFIKASI TTE BALMON SEMARANG\n"
            . "Jenis Dokumen: BAST Pengembalian Kendaraan (Peminjam)\n"
            . "No. Surat: " . $booking->letter_number . "\n"
            . "Penandatangan: " . $peminjamName . " (NIP: " . $peminjamNip . ")\n"
            . "Waktu Pengembalian: " . $peminjamSignedAt->isoFormat('D MMMM YYYY HH:mm') . " WIB\n"
            . "URL Verifikasi: " . route('bookings.show', $booking->letter_slug);

        $qrCodePeminjam = base64_encode(QrCode::format('svg')->size(100)->margin(1)->generate($ttePeminjamInfo));

        // 2. QR Code Penanggung Jawab Pengecek (Aktif di status checked & completed)
        $qrCodePenanggungJawab = null;
        if ($isPenanggungJawabSigned) {
            $ttePJInfo = "DOKUMEN TERVERIFIKASI TTE BALMON SEMARANG\n"
                . "Jenis Dokumen: BAST Pengembalian Kendaraan (Pengecek BMN)\n"
                . "No. Surat: " . $booking->letter_number . "\n"
                . "Penandatangan: " . $penanggungJawabName . " (NIP: " . $penanggungJawabNip . ")\n"
                . "Waktu Pemeriksaan: " . ($booking->returned_checked_at ? $booking->returned_checked_at->isoFormat('D MMMM YYYY HH:mm') : '-') . " WIB\n"
                . "URL Verifikasi: " . route('bookings.show', $booking->letter_slug);

            $qrCodePenanggungJawab = base64_encode(QrCode::format('svg')->size(100)->margin(1)->generate($ttePJInfo));
        }

        // 3. QR Code Approver Pengesahan (Aktif HANYA di status completed)
        $qrCodeApprover = null;
        if ($isApproverSigned) {
            $tteApproverInfo = "DOKUMEN TERVERIFIKASI TTE BALMON SEMARANG\n"
                . "Jenis Dokumen: BAST Pengembalian Kendaraan (Pengesahan Atasan)\n"
                . "No. Surat: " . $booking->letter_number . "\n"
                . "Penandatangan: " . $approverName . " (NIP: " . $approverNip . ")\n"
                . "Waktu Pengesahan: " . ($booking->returned_approved_at ? $booking->returned_approved_at->isoFormat('D MMMM YYYY HH:mm') : '-') . " WIB\n"
                . "URL Verifikasi: " . route('bookings.show', $booking->letter_slug);

            $qrCodeApprover = base64_encode(QrCode::format('svg')->size(100)->margin(1)->generate($tteApproverInfo));
        }

        $tteData = [
            'peminjam' => [
                'signed'    => true,
                'name'      => $peminjamName,
                'nip'       => $peminjamNip,
                'signed_at' => $peminjamSignedAt,
                'qr_code'   => $qrCodePeminjam,
            ],
            'penanggung_jawab' => [
                'signed'    => $isPenanggungJawabSigned,
                'name'      => $penanggungJawabName,
                'nip'       => $penanggungJawabNip,
                'signed_at' => $booking->returned_checked_at,
                'qr_code'   => $qrCodePenanggungJawab,
            ],
            'approver' => [
                'signed'    => $isApproverSigned,
                'name'      => $approverName,
                'nip'       => $approverNip,
                'signed_at' => $booking->returned_approved_at,
                'qr_code'   => $qrCodeApprover,
            ],
        ];

        $pdf = Pdf::loadView('pdf.bast-pengembalian', compact('booking', 'tteData'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('BAST_Pengembalian_' . $booking->letter_slug . '.pdf');
    }
}
