<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    /**
     * 1. Cetak Formulir Permohonan Peminjaman
     */
    public function printFormulirPermohonan(Booking $booking)
    {
        $booking->load(['user', 'vehicle', 'driver']);

        $pdf = Pdf::loadView('pdf.formulir-permohonan', compact('booking'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Formulir_Permohonan_' . $booking->letter_slug . '.pdf');
    }

    /**
     * 2. Cetak BAST Peminjaman / Serah Terima Kunci
     */
    public function printBastPeminjaman(Booking $booking)
    {
        if (!in_array($booking->status, ['approved', 'on_trip', 'completed'])) {
            abort(403, 'BAST Peminjaman hanya dapat dicetak setelah permohonan disetujui.');
        }

        $booking->load(['user', 'vehicle', 'driver', 'approver', 'log']);

        $pdf = Pdf::loadView('pdf.bast-peminjaman', compact('booking'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('BAST_Peminjaman_' . $booking->letter_slug . '.pdf');
    }

    /**
     * 3. Cetak Formulir Pengembalian
     */
    public function printFormulirPengembalian(Booking $booking)
    {
        if ($booking->status !== 'completed') {
            abort(403, 'Formulir Pengembalian hanya dapat dicetak setelah peminjaman selesai.');
        }

        $booking->load(['user', 'vehicle', 'driver', 'log']);

        $pdf = Pdf::loadView('pdf.formulir-pengembalian', compact('booking'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('Formulir_Pengembalian_' . $booking->letter_slug . '.pdf');
    }

    /**
     * 4. Cetak BAST Pengembalian
     */
    public function printBastPengembalian(Booking $booking)
    {
        if ($booking->status !== 'completed') {
            abort(403, 'BAST Pengembalian hanya dapat dicetak setelah peminjaman selesai.');
        }

        $booking->load(['user', 'vehicle', 'driver', 'log', 'returnApprover']);

        $pdf = Pdf::loadView('pdf.bast-pengembalian', compact('booking'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('BAST_Pengembalian_' . $booking->letter_slug . '.pdf');
    }
}
