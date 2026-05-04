<?php

namespace App\Modules\Booking\Mail;

use App\Modules\Booking\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Pod24 booking is confirmed — " . $this->booking->starts_at->format('D, M j H:i'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-confirmed');
    }
}
