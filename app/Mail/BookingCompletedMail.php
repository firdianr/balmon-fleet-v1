<?php

namespace App\Mail;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BookingCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Maksimal percobaan kirim sebelum dilewati ke failed_jobs agar tidak menyumbat queue
     */
    public int $tries = 2;

    /**
     * Jeda waktu (detik) sebelum mencoba ulang jika terjadi gangguan jaringan sementara
     */
    public int $backoff = 10;

    /**
     * Hapus job otomatis jika data booking sudah dihapus dari database
     */
    public bool $deleteWhenMissingModels = true;

    public Booking $booking;
    public string $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, string $recipientName)
    {
        $this->booking = $booking->loadMissing([
            'user', 'vehicle', 'driver', 'allocator', 'approver', 
            'returnChecker', 'returnApprover', 'log', 'signatures.user', 
            'startChecklist', 'endChecklist'
        ]);
        $this->recipientName = $recipientName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Peminjaman Armada Selesai & Dokumen Laporan - ' . $this->booking->letter_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-completed',
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        $booking = $this->booking;
        $slug = $booking->letter_slug;

        $docMap = [
            'formulir_peminjaman'   => "1_Formulir_Permohonan_{$slug}.pdf",
            'bast_peminjaman'       => "2_BAST_Peminjaman_{$slug}.pdf",
            'formulir_pengembalian' => "3_Formulir_Pengembalian_{$slug}.pdf",
            'bast_pengembalian'     => "4_BAST_Pengembalian_{$slug}.pdf",
        ];

        foreach ($docMap as $docType => $filename) {
            $archived = \App\Models\BookingDocument::where('booking_id', $booking->id)
                ->where('document_type', $docType)
                ->first();

            if ($archived) {
                $pdfBytes = \App\Services\DocumentArchiveService::getVerifiedDecryptedPdf($archived);
            } else {
                $viewName = \App\Services\DocumentArchiveService::getViewName($docType);
                $pdfBytes = Pdf::loadView($viewName, ['booking' => $booking, 'auditMeta' => null])
                    ->setPaper('a4', 'portrait')
                    ->output();
            }

            $attachments[] = Attachment::fromData(fn () => $pdfBytes, $filename)
                ->withMime('application/pdf');
        }

        return $attachments;
    }

    /**
     * Tangani kegagalan permanen tanpa menghentikan worker
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Antrean BookingCompletedMail gagal permanen untuk surat {$this->booking->letter_number}: " . $exception->getMessage());
    }
}