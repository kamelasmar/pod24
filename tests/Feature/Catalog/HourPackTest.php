<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;

it('creates an hour pack with hours, price, and expiry', function () {
    $facility = Facility::factory()->create();

    $pack = HourPack::factory()->for($facility)->create([
        'hours' => 10,
        'price_aed_cents' => 228600,
        'expiry_days' => 365,
    ]);

    expect($pack->hours)->toBe(10);
    expect($pack->price_aed_cents)->toBe(228600);
    expect($pack->expiry_days)->toBe(365);
});
