<?php

namespace App\Modules\Customers\Actions;

use App\Modules\Booking\Models\Booking;
use App\Modules\Customers\Models\HourPackTransaction;

class RedeemHourPackHours
{
    public function execute(Booking $booking, int $hoursToRedeem): void
    {
        if ($hoursToRedeem <= 0 || ! $booking->customer_id) {
            return;
        }

        HourPackTransaction::create([
            'customer_id' => $booking->customer_id,
            'facility_id' => $booking->facility_id,
            'hours' => -$hoursToRedeem,
            'type' => 'redeem',
            'booking_id' => $booking->id,
            'notes' => 'Booking ' . $booking->ulid,
        ]);
    }
}
