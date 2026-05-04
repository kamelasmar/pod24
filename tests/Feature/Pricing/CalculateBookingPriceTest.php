<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\PricingModifier;
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

it('applies weekend markup for hourly booking on Saturday', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'weekend', 'percentage' => 25,
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-06 10:00:00', 'Asia/Dubai'),  // Saturday
        ends_at:   CarbonImmutable::parse('2026-06-06 12:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->base_aed_cents)->toBe(20000);                  // 10000 × 2 hours
    expect($breakdown->weekend_markup_aed_cents)->toBe(5000);         // 25% of 20000
    expect($breakdown->subtotal())->toBe(25000);
});

it('applies no weekend markup for hourly booking on weekday', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'weekend', 'percentage' => 25,
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),  // Monday
        ends_at:   CarbonImmutable::parse('2026-06-08 12:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->weekend_markup_aed_cents)->toBe(0);
});

it('applies pro-rata weekend markup for hourly booking spanning Fri-Sat midnight', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'weekend', 'percentage' => 25,
    ]);

    // Fri 23:00 -> Sat 02:00 = 1 weekday hour + 2 weekend hours
    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-05 23:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-06 02:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->base_aed_cents)->toBe(30000);                  // 10000 × 3 hours
    expect($breakdown->weekend_markup_aed_cents)->toBe(5000);         // 25% × 2 weekend hrs × 10000
});

it('applies whole-package weekend markup for full-day booking on weekend', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'full_day', 'hours' => 8, 'price_aed_cents' => 200000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'weekend', 'percentage' => 25,
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'full_day',
        starts_at: CarbonImmutable::parse('2026-06-06 09:00:00', 'Asia/Dubai'),  // Saturday
        ends_at:   CarbonImmutable::parse('2026-06-06 17:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->weekend_markup_aed_cents)->toBe(50000);        // 25% × 200000
});
