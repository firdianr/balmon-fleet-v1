<?php

namespace App\Http\Controllers;

use App\Models\BookingDocument;
use App\Services\DocumentArchiveService;
use Illuminate\Http\Request;

class DocumentVerificationController extends Controller
{
    public function show(string $uuid)
    {
        $document = BookingDocument::with(['booking.user', 'booking.vehicle'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $isAuthentic = false;
        $statusMessage = '';

        try {
            DocumentArchiveService::getVerifiedDecryptedPdf($document);
            $isAuthentic = true;
            $statusMessage = 'Dokumen Asli & Terverifikasi (Checksum SHA-256 Cocok).';
        } catch (\Throwable $e) {
            $isAuthentic = false;
            $statusMessage = $e->getMessage();
        }

        return view('documents.verify', compact('document', 'isAuthentic', 'statusMessage'));
    }

    /**
     * Verifikasi mandiri dengan mengunggah file PDF untuk dicocokkan SHA-256-nya
     */
    public function checkUploadedFile(Request $request, string $uuid)
    {
        $request->validate([
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $document = BookingDocument::where('uuid', $uuid)->firstOrFail();
        $uploadedHash = hash_file('sha256', $request->file('pdf_file')->getRealPath());
        $isMatch = hash_equals($document->sha256_hash, $uploadedHash);

        return back()->with('file_check_result', [
            'is_match'      => $isMatch,
            'uploaded_hash' => $uploadedHash,
            'recorded_hash' => $document->sha256_hash,
        ]);
    }
}