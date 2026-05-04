<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Models\HourPackTransaction;

it('records a positive purchase and a negative redemption', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    HourPackTransaction::create([
        'customer_id' => $user->id,
        'facility_id' => $facility->id,
        'hours' => 10,
        'type' => 'purchase',
        'expires_at' => now()->addYear(),
    ]);

    HourPackTransaction::create([
        'customer_id' => $user->id,
        'facility_id' => $facility->id,
        'hours' => -2,
        'type' => 'redeem',
    ]);

    expect(HourPackTransaction::where('customer_id', $user->id)->sum('hours'))->toBe(8);
});
