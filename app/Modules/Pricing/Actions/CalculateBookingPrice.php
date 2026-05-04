<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\PricingModifier;
use App\Modules\Pricing\Exceptions\PricingNotConfigured;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use App\Modules\Pricing\ValueObjects\PriceBreakdown;
use Carbon\CarbonImmutable;

class CalculateBookingPrice
{
    public function execute(BookingDraft $draft): PriceBreakdown
    {
        $base = $this->base($draft);
        return new PriceBreakdown(
            base_aed_cents: $base,
            weekend_markup_aed_cents: $this->weekendMarkup($draft, $base),
            after_hours_markup_aed_cents: $this->afterHoursMarkup($draft, $base),
        );
    }

    private function base(BookingDraft $draft): int
    {
        $row = FacilityPricing::where([
            'facility_id' => $draft->facility_id,
            'service_tier_id' => $draft->service_tier_id,
            'package_type' => $draft->package_type,
        ])->first();

        if (! $row) {
            throw new PricingNotConfigured(sprintf(
                'No pricing for facility=%d tier=%d package=%s',
                $draft->facility_id, $draft->service_tier_id, $draft->package_type
            ));
        }

        return match ($draft->package_type) {
            'hourly' => $row->price_aed_cents * $draft->totalHours(),
            'multi_day' => $row->price_aed_cents * $this->numberOfDays($draft),
            'half_day', 'full_day' => $row->price_aed_cents,
            default => throw new PricingNotConfigured("Unknown package_type {$draft->package_type}"),
        };
    }

    private function numberOfDays(BookingDraft $draft): int
    {
        return max(1, (int) $draft->starts_at->startOfDay()->diffInDays($draft->ends_at->startOfDay()) + 1);
    }

    private function weekendMarkup(BookingDraft $draft, int $base): int
    {
        $modifier = PricingModifier::where([
            'facility_id' => $draft->facility_id,
            'type' => 'weekend',
        ])->first();

        if (! $modifier) {
            return 0;
        }

        if ($draft->package_type === 'hourly') {
            $weekendHours = $this->countWeekendHours($draft->starts_at, $draft->ends_at);
            if ($weekendHours === 0) {
                return 0;
            }
            $hourlyRate = (int) ($base / $draft->totalHours());
            return (int) round($hourlyRate * $weekendHours * $modifier->percentage / 100);
        }

        // half_day, full_day, multi_day: full package gets markup if ANY of its hours fall on weekend
        if ($this->countWeekendHours($draft->starts_at, $draft->ends_at) > 0) {
            return (int) round($base * $modifier->percentage / 100);
        }
        return 0;
    }

    private function countWeekendHours(CarbonImmutable $start, CarbonImmutable $end): int
    {
        $weekend = 0;
        $cursor = $start;
        while ($cursor < $end) {
            // dayOfWeek: 0 = Sunday, 6 = Saturday — UAE weekend = Sat (6) and Sun (0).
            if (in_array($cursor->dayOfWeek, [0, 6], true)) {
                $weekend++;
            }
            $cursor = $cursor->addHour();
        }
        return $weekend;
    }

    private function afterHoursMarkup(BookingDraft $draft, int $base): int
    {
        $modifier = PricingModifier::where([
            'facility_id' => $draft->facility_id,
            'type' => 'after_hours',
        ])->first();

        if (! $modifier || ! $modifier->after_hours_start || ! $modifier->after_hours_end) {
            return 0;
        }

        $afterHoursHours = $this->countAfterHoursHours(
            $draft->starts_at, $draft->ends_at,
            $modifier->after_hours_start, $modifier->after_hours_end,
        );

        if ($afterHoursHours === 0) {
            return 0;
        }

        if ($draft->package_type === 'hourly') {
            $hourlyRate = (int) ($base / $draft->totalHours());
            return (int) round($hourlyRate * $afterHoursHours * $modifier->percentage / 100);
        }

        return (int) round($base * $modifier->percentage / 100);
    }

    private function countAfterHoursHours(
        CarbonImmutable $start, CarbonImmutable $end,
        string $afterHoursStart, string $afterHoursEnd,
    ): int {
        [$startH, $startM] = array_map('intval', explode(':', $afterHoursStart));
        [$endH, $endM] = array_map('intval', explode(':', $afterHoursEnd));
        $startMinuteOfDay = $startH * 60 + $startM;
        $endMinuteOfDay = $endH * 60 + $endM;

        $count = 0;
        $cursor = $start;
        while ($cursor < $end) {
            $minuteOfDay = $cursor->hour * 60 + $cursor->minute;
            $isAfterHours = $startMinuteOfDay < $endMinuteOfDay
                ? ($minuteOfDay >= $startMinuteOfDay && $minuteOfDay < $endMinuteOfDay)
                : ($minuteOfDay >= $startMinuteOfDay || $minuteOfDay < $endMinuteOfDay);
            if ($isAfterHours) {
                $count++;
            }
            $cursor = $cursor->addHour();
        }
        return $count;
    }
}
