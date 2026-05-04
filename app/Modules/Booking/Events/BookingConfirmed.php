<?php

namespace App\Modules\Booking\Events;

use App\Modules\Booking\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

class BookingConfirmed
{
    use Dispatchable;

    public function __construct(public Booking $booking) {}
}
