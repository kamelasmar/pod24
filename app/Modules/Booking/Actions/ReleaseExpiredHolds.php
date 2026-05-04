<?php

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;

class ReleaseExpiredHolds
{
    public function execute(): int
    {
        return Booking::where('status', BookingStatus::Hold->value)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now())
            ->update([
                'status' => BookingStatus::Cancelled->value,
                'cancelled_at' => now(),
                'cancelled_by' => 'admin',
            ]);
    }
}
