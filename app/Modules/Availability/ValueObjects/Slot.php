<?php

namespace App\Modules\Availability\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class Slot
{
    public function __construct(
        public CarbonImmutable $starts_at,
        public CarbonImmutable $ends_at,
    ) {}
}
