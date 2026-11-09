<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class BookingCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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

        $pdfFormulirPermohonan = Pdf::loadView('pdf.formulir-permohonan', compact('booking'))->setPaper('a4', 'portrait');
        $attachments[] = Attachment::fromData(fn () => $pdfFormulirPermohonan->output(), '1_Formulir_Permohonan_' . $slug . '.pdf')->withMime('application/pdf');

        $pdfBastPeminjaman = Pdf::loadView('pdf.bast-peminjaman', compact('booking'))->setPaper('a4', 'portrait');
        $attachments[] = Attachment::fromData(fn () => $pdfBastPeminjaman->output(), '2_BAST_Peminjaman_' . $slug . '.pdf')->withMime('application/pdf');

        $pdfFormulirPengembalian = Pdf::loadView('pdf.formulir-pengembalian', compact('booking'))->setPaper('a4', 'portrait');
        $attachments[] = Attachment::fromData(fn () => $pdfFormulirPengembalian->output(), '3_Formulir_Pengembalian_' . $slug . '.pdf')->withMime('application/pdf');

        $pdfBastPengembalian = Pdf::loadView('pdf.bast-pengembalian', compact('booking'))->setPaper('a4', 'portrait');
        $attachments[] = Attachment::fromData(fn () => $pdfBastPengembalian->output(), '4_BAST_Pengembalian_' . $slug . '.pdf')->withMime('application/pdf');

        return $attachments;
    }
}