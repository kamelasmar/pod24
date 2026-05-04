<?php

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;

it('creates a refund tier for a facility', function () {
    $facility = Facility::factory()->create();

    $policy = CancellationPolicy::factory()->for($facility)->create([
        'hours_before_min' => 168,
        'refund_percentage' => 100,
    ]);

    expect($policy->hours_before_min)->toBe(168);
    expect($policy->refund_percentage)->toBe(100);
});
