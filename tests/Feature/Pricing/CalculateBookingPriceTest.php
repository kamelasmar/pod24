<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Pricing\Actions\CalculateBookingPrice;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

it('computes hourly base price from facility_pricing × hours', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create(['base_hourly_rate_aed_cents' => 25400]);
    FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-01 10:00:00', 'Asia/Dubai'),  // Monday
        ends_at:   CarbonImmutable::parse('2026-06-01 13:00:00', 'Asia/Dubai'),
    );

    $action = app(CalculateBookingPrice::class);
    $breakdown = $action->execute($draft);

    expect($breakdown->base_aed_cents)->toBe(76200);   // 25400 × 3
    expect($breakdown->subtotal())->toBe(76200);
    expect($breakdown->vat())->toBe(3810);             // 5% of 76200
    expect($breakdown->total())->toBe(80010);
});

it('uses the half_day fixed price when package_type=half_day', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'half_day',
        'hours' => 4,
        'price_aed_cents' => 91440,                      // admin-set, NOT 4 × hourly
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'half_day',
        starts_at: CarbonImmutable::parse('2026-06-01 09:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-01 13:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->base_aed_cents)->toBe(91440);    // fixed, not × hours
});

it('throws when no pricing row exists for the (facility, tier, package_type) cell', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-01 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-01 11:00:00', 'Asia/Dubai'),
    );

    expect(fn () => app(CalculateBookingPrice::class)->execute($draft))
        ->toThrow(\App\Modules\Pricing\Exceptions\PricingNotConfigured::class);
});
