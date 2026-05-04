<?php

use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Catalog\Models\Facility;

it('creates an availability blackout with start, end and reason', function () {
    $facility = Facility::factory()->create();

    $blackout = AvailabilityBlackout::create([
        'facility_id' => $facility->id,
        'starts_at' => '2026-06-01 08:00:00',
        'ends_at' => '2026-06-01 18:00:00',
        'reason' => 'Annual maintenance',
    ]);

    expect($blackout->fresh()->starts_at->toDateTimeString())->toBe('2026-06-01 08:00:00');
    expect($blackout->fresh()->ends_at->toDateTimeString())->toBe('2026-06-01 18:00:00');
    expect($blackout->fresh()->reason)->toBe('Annual maintenance');
});
