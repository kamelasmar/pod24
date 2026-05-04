<?php

namespace App\Modules\Pricing\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class BookingDraft
{
    public function __construct(
        public int $facility_id,
        public int $service_tier_id,
        public string $package_type,         // hourly | half_day | full_day | multi_day
        public CarbonImmutable $starts_at,
        public CarbonImmutable $ends_at,
        public array $addons = [],            // [['addon_id' => int, 'qty' => int], ...]
    ) {}

    public function totalHours(): int
    {
        return (int) $this->starts_at->diffInHours($this->ends_at);
    }
}
