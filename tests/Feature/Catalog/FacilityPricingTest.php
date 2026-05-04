<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;

it('stores a price for a facility-tier-package cell', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    $pricing = FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);

    expect($pricing->price_aed_cents)->toBe(25400);
});

it('enforces uniqueness on (facility, tier, package_type)', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);

    expect(fn () => FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 30000,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
