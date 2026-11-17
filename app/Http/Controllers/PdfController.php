<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDocument;
use App\Services\DocumentArchiveService;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    private function streamDocumentOrDraft(Booking $booking, string $documentType, string $downloadFilename, array $relations)
    {
        // 1. Cek apakah dokumen final sudah terenkripsi di private storage
        $archivedDoc = BookingDocument::where('booking_id', $booking->id)
            ->where('document_type', $documentType)
            ->first();

        if ($archivedDoc) {
            $decryptedPdf = DocumentArchiveService::getVerifiedDecryptedPdf($archivedDoc);

            return response($decryptedPdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $downloadFilename . '"',
            ]);
        }

        // 2. Jika belum lengkap semua TTD, render pratinjau Draft
        $booking->load($relations);
        $docSignatures = $booking->signatures->where('document_type', $documentType)->values();

        $auditMeta = [
            'uuid'             => null,
            'is_final'         => false,
            'finalized_at'     => null,
            'verification_url' => null,
            'signers'          => $docSignatures,
        ];

        $viewName = DocumentArchiveService::getViewName($documentType);
        $pdf = Pdf::loadView($viewName, compact('booking', 'auditMeta'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream($downloadFilename);
    }

    public function printFormulirPermohonan(Booking $booking)
    {
        return $this->streamDocumentOrDraft(
            $booking,
            'formulir_peminjaman',
            'Formulir_Permohonan_' . $booking->letter_slug . '.pdf',
            ['user', 'vehicle', 'driver', 'signatures.user']
        );
    }

    public function printBastPeminjaman(Booking $booking)
    {
        if (!in_array($booking->status, ['waiting', 'approved', 'trip_started', 'on_trip', 'trip_completed', 'returned', 'checked', 'completed'])) {
            abort(403, 'BAST Peminjaman belum dapat dicetak pada status saat ini.');
        }

        return $this->streamDocumentOrDraft(
            $booking,
            'bast_peminjaman',
            'BAST_Peminjaman_' . $booking->letter_slug . '.pdf',
            ['user', 'vehicle', 'driver', 'allocator', 'approver', 'signatures.user']
        );
    }

    public function printFormulirPengembalian(Booking $booking)
    {
        if (!in_array($booking->status, ['returned', 'checked', 'completed'])) {
            abort(403, 'Formulir Pengembalian hanya dapat dicetak setelah kendaraan dikembalikan.');
        }

        return $this->streamDocumentOrDraft(
            $booking,
            'formulir_pengembalian',
            'Formulir_Pengembalian_' . $booking->letter_slug . '.pdf',
            ['user', 'vehicle', 'driver', 'log', 'signatures.user']
        );
    }

    public function printBastPengembalian(Booking $booking)
    {
        if (!in_array($booking->status, ['returned', 'checked', 'completed'])) {
            abort(403, 'BAST Pengembalian hanya dapat dicetak setelah kendaraan dikembalikan.');
        }

        return $this->streamDocumentOrDraft(
            $booking,
            'bast_pengembalian',
            'BAST_Pengembalian_' . $booking->letter_slug . '.pdf',
            ['user', 'vehicle', 'driver', 'log', 'returnChecker', 'returnApprover', 'signatures.user', 'endChecklist']
        );
    }
}