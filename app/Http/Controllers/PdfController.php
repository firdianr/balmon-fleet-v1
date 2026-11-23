<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDocument;
use App\Models\BookingSignature;
use App\Services\DocumentArchiveService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    private function authorizeBookingAccess(Booking $booking): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        if (in_array($user->role, ['admin_app', 'admin_lap', 'approver'])) {
            return;
        }

        $participants = is_array($booking->participants) ? $booking->participants : [];
        $isParticipant = in_array($user->id, $participants, true)
            || in_array((string) $user->id, $participants, true);

        $isAuthorizedUser = (int) $booking->user_id === (int) $user->id
            || ($booking->driver_id && (int) $booking->driver_id === (int) $user->id)
            || $isParticipant;

        if (!$isAuthorizedUser) {
            abort(403, 'Anda tidak memiliki hak akses terhadap dokumen atau berkas peminjaman ini.');
        }
    }

    private function streamDocumentOrDraft(Booking $booking, string $documentType, string $downloadFilename, array $relations)
    {
        $this->authorizeBookingAccess($booking);

        // 1. Cek apakah dokumen final sudah terenkripsi di private storage
        $archivedDoc = BookingDocument::where('booking_id', $booking->id)
            ->where('document_type', $documentType)
            ->first();

        if ($archivedDoc) {
            $decryptedPdf = DocumentArchiveService::getVerifiedDecryptedPdf($archivedDoc);

            return response($decryptedPdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $downloadFilename . '"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, private',
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
        $this->authorizeBookingAccess($booking);

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
        $this->authorizeBookingAccess($booking);

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
        $this->authorizeBookingAccess($booking);

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

    public function streamLetterFile(Booking $booking)
    {
        $this->authorizeBookingAccess($booking);

        return $this->servePrivatePdf(
            $booking->letter_file,
            'Surat_Permohonan_' . $booking->letter_slug . '.pdf'
        );
    }

    public function streamReturnLetterFile(Booking $booking)
    {
        $this->authorizeBookingAccess($booking);

        return $this->servePrivatePdf(
            $booking->return_letter_file,
            'Surat_Tugas_' . $booking->letter_slug . '.pdf'
        );
    }

    public function streamSignature(Booking $booking, BookingSignature $signature)
    {
        $this->authorizeBookingAccess($booking);

        if ((int) $signature->booking_id !== (int) $booking->id) {
            abort(403, 'Tanda tangan tidak sesuai dengan data peminjaman ini.');
        }

        if (!$signature->signature_path || !Storage::disk('local')->exists($signature->signature_path)) {
            abort(404, 'Berkas tanda tangan tidak ditemukan.');
        }

        return response(Storage::disk('local')->get($signature->signature_path), 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'inline; filename="' . basename($signature->signature_path) . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, private',
        ]);
    }

    private function servePrivatePdf(?string $path, string $downloadFilename)
    {
        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'Berkas surat tidak ditemukan di penyimpanan privat.');
        }

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $downloadFilename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, private',
        ]);
    }
}