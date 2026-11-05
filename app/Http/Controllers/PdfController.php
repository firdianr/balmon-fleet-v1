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
        // Load relasi user, vehicle, driver, dan signatures
        $booking->load(['user', 'vehicle', 'driver', 'signatures.user']);

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

        // Load relasi allocator, approver, serta signatures
        $booking->load(['user', 'vehicle', 'driver', 'allocator', 'approver', 'signatures.user', 'startChecklist']);

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

        // Load relasi utama beserta TTD
        $booking->load(['user', 'vehicle', 'driver', 'log', 'signatures.user']);

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

        // Load relasi pemeriksaan, persetujuan pengembalian, serta TTD
        $booking->load(['user', 'vehicle', 'driver', 'log', 'returnChecker', 'returnApprover', 'signatures.user', 'endChecklist']);

        $pdf = Pdf::loadView('pdf.bast-pengembalian', compact('booking'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('BAST_Pengembalian_' . $booking->letter_slug . '.pdf');
    }
}