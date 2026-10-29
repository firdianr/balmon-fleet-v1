<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;

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
        if (!in_array($booking->status, ['waiting', 'approved', 'on_trip', 'returned', 'checked', 'completed'])) {
            abort(403, 'BAST Peminjaman belum dapat dicetak pada status saat ini.');
        }

        $booking->load(['user', 'vehicle', 'driver', 'allocator', 'approver']);

        $pdf = Pdf::loadView('pdf.bast-peminjaman', compact('booking'))
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

        $pdf = Pdf::loadView('pdf.formulir-pengembalian', compact('booking'))
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

        $pdf = Pdf::loadView('pdf.bast-pengembalian', compact('booking'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('BAST_Pengembalian_' . $booking->letter_slug . '.pdf');
    }
}