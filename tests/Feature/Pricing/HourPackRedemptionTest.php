<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Customers\Models\HourPackTransaction;
use App\Modules\Pricing\Actions\CalculateBookingPrice;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

it('credits AED equivalent of redeemed pack hours at Recording Only base rate', function () {
    $facility = Facility::factory()->create();
    $recordingTier = ServiceTier::factory()->for($facility)->create([
        'name' => 'Recording Only', 'base_hourly_rate_aed_cents' => 25400,
    ]);
    $liveTier = ServiceTier::factory()->for($facility)->create([
        'name' => 'Live Mix', 'base_hourly_rate_aed_cents' => 35400,
    ]);
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $liveTier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 35400,
    ]);

    $user = User::factory()->create();
    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase', 'expires_at' => now()->addYear(),
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $liveTier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 12:00:00', 'Asia/Dubai'),
        requestedPackHours: 2,
        customer_id: $user->id,
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);

    // base = 35400 × 2 = 70800
    // credit value = 25400 × 2 = 50800 (Recording Only base rate, not Live Mix)
    expect($breakdown->base_aed_cents)->toBe(70800);
    expect($breakdown->hour_pack_credit_value_aed_cents)->toBe(50800);
    expect($breakdown->subtotal())->toBe(70800 - 50800);  // = 20000
});

it('caps redeemed hours at the customer balance', function () {
    $facility = Facility::factory()->create();
    $recordingTier = ServiceTier::factory()->for($facility)->create([
        'name' => 'Recording Only', 'base_hourly_rate_aed_cents' => 25400,
    ]);
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $recordingTier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 25400,
    ]);

    $user = User::factory()->create();
    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 1, 'type' => 'purchase', 'expires_at' => now()->addYear(),
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $recordingTier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 13:00:00', 'Asia/Dubai'),  // 3 hours
        requestedPackHours: 5,    // requested more than balance
        customer_id: $user->id,
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);

    // Only 1 hour can be credited (balance limit)
    expect($breakdown->hour_pack_credit_value_aed_cents)->toBe(25400);
});
