<?php

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Models\Booking;

class ConfirmBooking
{
    public function execute(Booking $booking, string $paymentIntentId): void
    {
        if ($booking->status === BookingStatus::Confirmed) {
            return; // idempotent
        }

        $booking->update([
            'status' => BookingStatus::Confirmed->value,
            'paid_at' => now(),
            'stripe_payment_intent_id' => $paymentIntentId,
            'hold_expires_at' => null,
        ]);

        BookingConfirmed::dispatch($booking->fresh());
    }
}
