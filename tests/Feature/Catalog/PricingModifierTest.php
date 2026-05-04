<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\PricingModifier;

it('creates a weekend modifier with a percentage', function () {
    $facility = Facility::factory()->create();

    $mod = PricingModifier::factory()->for($facility)->create([
        'type' => 'weekend',
        'percentage' => 25,
    ]);

    expect($mod->type)->toBe('weekend');
    expect($mod->percentage)->toBe(25);
});

it('creates an after-hours modifier with start/end window', function () {
    $facility = Facility::factory()->create();

    $mod = PricingModifier::factory()->for($facility)->create([
        'type' => 'after_hours',
        'percentage' => 25,
        'after_hours_start' => '18:00',
        'after_hours_end' => '09:00',
    ]);

    expect($mod->after_hours_start)->toBe('18:00:00');
});
