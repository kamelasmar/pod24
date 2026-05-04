<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Pricing\Exceptions\PricingNotConfigured;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use App\Modules\Pricing\ValueObjects\PriceBreakdown;

class CalculateBookingPrice
{
    public function execute(BookingDraft $draft): PriceBreakdown
    {
        $base = $this->base($draft);

        return new PriceBreakdown(
            base_aed_cents: $base,
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
}
