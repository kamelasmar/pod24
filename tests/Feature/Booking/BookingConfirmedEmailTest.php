<?php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Mail\BookingConfirmedMail;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Support\Facades\Mail;

it('sends a SendGrid email when BookingConfirmed event fires', function () {
    Mail::fake();
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::Confirmed->value,
        'contact_email' => 'guest@example.com',
    ]);

    BookingConfirmed::dispatch($booking);

    Mail::assertSent(BookingConfirmedMail::class, function ($mail) use ($booking) {
        return $mail->hasTo($booking->contact_email)
            && $mail->booking->id === $booking->id;
    });
});
