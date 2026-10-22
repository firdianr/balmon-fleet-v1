<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Barryvdh\DomPDF\Facade\Pdf;

class BookingSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Permohonan Peminjaman Armada Berhasil Diajukan - ' . $this->booking->letter_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-submitted',
        );
    }

    public function attachments(): array
    {
        // Mematikan lampiran sementara waktu:
        return [];
        
        // Generasi PDF Formulir Permohonan dari view PDF Anda
        $pdf = Pdf::loadView('pdf.formulir-permohonan', ['booking' => $this->booking]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'Formulir-Permohonan-' . $this->booking->letter_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
