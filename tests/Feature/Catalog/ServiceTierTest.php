<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('belongs to a facility', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create([
        'name' => 'Recording Only',
        'base_hourly_rate_aed_cents' => 25400,
    ]);

    expect($tier->facility->id)->toBe($facility->id);
    expect($tier->base_hourly_rate_aed_cents)->toBe(25400);
});

it('orders tiers by sort_order ascending', function () {
    $facility = Facility::factory()->create();
    ServiceTier::factory()->for($facility)->create(['name' => 'B', 'sort_order' => 2]);
    ServiceTier::factory()->for($facility)->create(['name' => 'A', 'sort_order' => 1]);

    $tiers = $facility->serviceTiers()->orderBy('sort_order')->pluck('name');
    expect($tiers->toArray())->toBe(['A', 'B']);
});
