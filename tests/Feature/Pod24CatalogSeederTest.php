<?php

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\PricingModifier;
use App\Modules\Catalog\Models\ServiceTier;

it('seeds the Pod24 facility with 4 service tiers', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    expect($facility)->not->toBeNull();
    expect($facility->serviceTiers()->count())->toBe(4);
});

it('seeds Pod24 with the documented hourly base rates', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();

    $recording = $facility->serviceTiers()->where('name', 'Recording Only')->first();
    expect($recording->base_hourly_rate_aed_cents)->toBe(25400);

    $liveMix = $facility->serviceTiers()->where('name', 'Live Mix')->first();
    expect($liveMix->base_hourly_rate_aed_cents)->toBe(35400); // 254 + 100

    $liveMixEdit = $facility->serviceTiers()->where('name', 'Live Mix + Standard Edit')->first();
    expect($liveMixEdit->base_hourly_rate_aed_cents)->toBe(75400); // 254 + 500

    $liveMixEditStream = $facility->serviceTiers()->where('name', 'Live Mix + Standard Edit + Live Stream')->first();
    expect($liveMixEditStream->base_hourly_rate_aed_cents)->toBe(105400); // 254 + 800
});

it('seeds weekend and after-hours modifiers at 25 percent', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    $weekend = PricingModifier::where(['facility_id' => $facility->id, 'type' => 'weekend'])->first();
    $afterHours = PricingModifier::where(['facility_id' => $facility->id, 'type' => 'after_hours'])->first();
    expect($weekend->percentage)->toBe(25);
    expect($afterHours->percentage)->toBe(25);
    // Re-fetch from DB so we verify the round-tripped Postgres TIME format ('HH:MM:SS')
    expect($afterHours->fresh()->after_hours_start)->toBe('18:00:00');
    expect($afterHours->fresh()->after_hours_end)->toBe('09:00:00');
});

it('seeds 7d/3d/0 cancellation tiers', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    $tiers = CancellationPolicy::where('facility_id', $facility->id)->orderByDesc('hours_before_min')->get();
    expect($tiers)->toHaveCount(3);
    expect($tiers[0]->hours_before_min)->toBe(168);
    expect($tiers[0]->refund_percentage)->toBe(100);
    expect($tiers[1]->hours_before_min)->toBe(72);
    expect($tiers[1]->refund_percentage)->toBe(50);
    expect($tiers[2]->hours_before_min)->toBe(0);
    expect($tiers[2]->refund_percentage)->toBe(0);
});

it('seeds pricing matrix for Recording Only tier across all package types', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    $recording = $facility->serviceTiers()->where('name', 'Recording Only')->first();

    expect(FacilityPricing::where(['service_tier_id' => $recording->id, 'package_type' => 'hourly'])->first()->price_aed_cents)->toBe(25400);
    expect(FacilityPricing::where(['service_tier_id' => $recording->id, 'package_type' => 'half_day'])->first()->price_aed_cents)->toBe(91440); // 4h × 254 × 0.9 (10% half-day discount)
});
