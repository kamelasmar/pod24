<?php

use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Catalog\Models\PricingModifier;
use App\Modules\Catalog\Models\ServiceTier;

it('cascades delete from Facility to all child catalog rows', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);
    Addon::factory()->for($facility)->create();
    HourPack::factory()->for($facility)->create();
    PricingModifier::factory()->for($facility)->create(['type' => 'weekend']);
    CancellationPolicy::factory()->for($facility)->create();

    expect(ServiceTier::where('facility_id', $facility->id)->count())->toBe(1);

    $facility->delete();

    expect(ServiceTier::where('facility_id', $facility->id)->count())->toBe(0);
    expect(FacilityPricing::where('facility_id', $facility->id)->count())->toBe(0);
    expect(Addon::where('facility_id', $facility->id)->count())->toBe(0);
    expect(HourPack::where('facility_id', $facility->id)->count())->toBe(0);
    expect(PricingModifier::where('facility_id', $facility->id)->count())->toBe(0);
    expect(CancellationPolicy::where('facility_id', $facility->id)->count())->toBe(0);
});
