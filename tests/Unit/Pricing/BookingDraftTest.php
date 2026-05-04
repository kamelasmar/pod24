<?php

use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

it('captures the booking inputs needed for pricing', function () {
    $draft = new BookingDraft(
        facility_id: 1,
        service_tier_id: 2,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-01 10:00:00', 'Asia/Dubai'),
        ends_at: CarbonImmutable::parse('2026-06-01 13:00:00', 'Asia/Dubai'),
        addons: [['addon_id' => 5, 'qty' => 1]],
    );

    expect($draft->facility_id)->toBe(1);
    expect($draft->totalHours())->toBe(3);
    expect($draft->addons)->toHaveCount(1);
});

it('reports total hours from the time window', function () {
    $draft = new BookingDraft(
        facility_id: 1,
        service_tier_id: 1,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-01 09:00:00', 'Asia/Dubai'),
        ends_at: CarbonImmutable::parse('2026-06-01 13:00:00', 'Asia/Dubai'),
    );
    expect($draft->totalHours())->toBe(4);
});
