<?php

use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\Facility;

it('creates an addon attached to a facility', function () {
    $facility = Facility::factory()->create();

    $addon = Addon::factory()->for($facility)->create([
        'name' => ['en' => 'Episode editing'],
        'price_aed_cents' => 50000,
    ]);

    expect($addon->getTranslation('name', 'en'))->toBe('Episode editing');
    expect($addon->price_aed_cents)->toBe(50000);
    expect($addon->facility->id)->toBe($facility->id);
});
