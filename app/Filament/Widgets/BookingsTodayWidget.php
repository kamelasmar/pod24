<?php

namespace App\Filament\Widgets;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingsTodayWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $tomorrow = now()->endOfDay();

        return [
            Stat::make(
                "Today's bookings",
                Booking::whereBetween('starts_at', [$today, $tomorrow])
                    ->where('status', BookingStatus::Confirmed->value)
                    ->count(),
            ),
            Stat::make(
                'Active holds',
                Booking::where('status', BookingStatus::Hold->value)->count(),
            ),
        ];
    }
}
