<?php

namespace App\Modules\Booking\Enums;

enum BookingStatus: string
{
    case Hold = 'hold';
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** Slots in these states block the calendar. */
    public function occupiesSlot(): bool
    {
        return in_array($this, [self::Hold, self::PendingPayment, self::Confirmed], true);
    }

    public static function occupyingValues(): array
    {
        return array_map(fn ($c) => $c->value, array_filter(self::cases(), fn ($c) => $c->occupiesSlot()));
    }
}
