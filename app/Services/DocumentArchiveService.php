<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentArchiveService
{
    /**
     * Pemetaan jenis dokumen ke view Blade PDF
     */
    public static function getViewName(string $documentType): string
    {
        return match ($documentType) {
            'formulir_peminjaman'   => 'pdf.formulir-permohonan',
            'bast_peminjaman'       => 'pdf.bast-peminjaman',
            'formulir_pengembalian' => 'pdf.formulir-pengembalian',
            'bast_pengembalian'     => 'pdf.bast-pengembalian',
            default                 => throw new \InvalidArgumentException("Jenis dokumen tidak dikenal: {$documentType}"),
        };
    }

    /**
     * Generate PDF Final, hitung SHA-256, enkripsi AES-256-CBC, dan simpan ke private storage
     */
    public static function archiveDocument(Booking $booking, string $documentType): BookingDocument
    {
        $booking->load([
            'user', 'vehicle', 'driver', 'allocator', 'approver',
            'returnChecker', 'returnApprover', 'log', 'signatures.user',
            'startChecklist', 'endChecklist',
        ]);

        // Cek jika sudah ada arsip lama untuk dokumen yang sama (misal kasus re-assign)
        $existingDoc = BookingDocument::where('booking_id', $booking->id)
            ->where('document_type', $documentType)
            ->first();

        if ($existingDoc && $existingDoc->file_path && Storage::disk('local')->exists($existingDoc->file_path)) {
            Storage::disk('local')->delete($existingDoc->file_path);
        }

        $uuid = $existingDoc ? $existingDoc->uuid : (string) Str::uuid();
        $finalizedAt = now();

        // Kumpulkan snapshot penanda tangan untuk dokumen ini
        $docSignatures = $booking->signatures
            ->where('document_type', $documentType)
            ->values();

        $signersSnapshot = $docSignatures->map(function ($sig) {
            return [
                'role'       => $sig->signer_role,
                'name'       => $sig->user?->name ?? '-',
                'nip'        => $sig->user?->nip ?? '-',
                'jabatan'    => $sig->user?->jabatan ?? '-',
                'signed_at'  => $sig->signed_at?->toIso8601String(),
                'signed_wib' => $sig->signed_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') . ' WIB',
                'ip_address' => $sig->ip_address ?? '-',
            ];
        })->toArray();

        $auditMeta = [
            'uuid'             => $uuid,
            'is_final'         => true,
            'finalized_at'     => $finalizedAt,
            'verification_url' => route('documents.verify', ['uuid' => $uuid]),
            'signers'          => $docSignatures,
        ];

        // Render PDF mentah dengan menyertakan metadata audit trail
        $viewName = self::getViewName($documentType);
        $rawPdf = Pdf::loadView($viewName, [
            'booking'   => $booking,
            'auditMeta' => $auditMeta,
        ])->setPaper('a4', 'portrait')->output();

        // 1. Hitung Hash SHA-256 dari biner PDF final (sebelum enkripsi)
        $sha256Hash = hash('sha256', $rawPdf);
        $fileSize   = strlen($rawPdf);

        // 2. Enkripsi biner PDF dengan AES-256-CBC + HMAC-SHA256 bawaan Laravel
        $encryptedPdf = Crypt::encrypt($rawPdf);

        // 3. Simpan ke private storage (storage/app/private/documents/...)
        $filePath = 'documents/' . now()->format('Y/m') . '/' . $documentType . '_' . $booking->letter_slug . '_' . $uuid . '.pdf.enc';
        Storage::disk('local')->put($filePath, $encryptedPdf);

        // 4. Catat metadata & hash ke tabel booking_documents
        return BookingDocument::updateOrCreate(
            [
                'booking_id'    => $booking->id,
                'document_type' => $documentType,
            ],
            [
                'uuid'              => $uuid,
                'file_path'         => $filePath,
                'sha256_hash'       => $sha256Hash,
                'encryption_cipher' => 'AES-256-CBC',
                'file_size'         => $fileSize,
                'signers_snapshot'  => $signersSnapshot,
                'finalized_at'      => $finalizedAt,
            ]
        );
    }

    /**
     * Mendekripsi file PDF dari private storage sekaligus memverifikasi integritas SHA-256
     */
    public static function getVerifiedDecryptedPdf(BookingDocument $document): string
    {
        if (!Storage::disk('local')->exists($document->file_path)) {
            throw new \RuntimeException('Berkas dokumen terenkripsi tidak ditemukan di penyimpanan privat.');
        }

        $encryptedContent = Storage::disk('local')->get($document->file_path);
        $decryptedPdf     = Crypt::decrypt($encryptedContent);

        // Validasi integritas matematis SHA-256
        $currentHash = hash('sha256', $decryptedPdf);
        if (!hash_equals($document->sha256_hash, $currentHash)) {
            throw new \RuntimeException('Peringatan Keamanan: Integritas file PDF tidak valid (Hash SHA-256 tidak cocok).');
        }

        return $decryptedPdf;
    }
}