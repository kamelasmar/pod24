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
        $weekendMarkup = $this->weekendMarkup($draft, $base);

        return new PriceBreakdown(
            base_aed_cents: $base,
            weekend_markup_aed_cents: $weekendMarkup,
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
}
