<?php

namespace App\Modules\Booking\Listeners;

use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Mail\BookingConfirmedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmedEmail implements ShouldQueue
{
    public function handle(BookingConfirmed $event): void
    {
        Mail::to($event->booking->contact_email)->send(new BookingConfirmedMail($event->booking));
    }
}
