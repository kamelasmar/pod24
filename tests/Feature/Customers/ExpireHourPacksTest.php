<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Actions\ExpireHourPacks;
use App\Modules\Customers\Models\HourPackTransaction;

it('inserts expire rows that zero out un-expired purchase rows past their expires_at', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase',
        'expires_at' => now()->subDay(),
    ]);

    $count = app(ExpireHourPacks::class)->execute();

    expect($count)->toBe(1);
    $expireRow = HourPackTransaction::where('type', 'expire')->first();
    expect($expireRow->hours)->toBe(-10);
});

it('does not double-expire an already-expired pack', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    $purchase = HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase',
        'expires_at' => now()->subDay(),
    ]);
    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => -10, 'type' => 'expire',
        'notes' => 'Linked purchase ' . $purchase->id,
    ]);

    $count = app(ExpireHourPacks::class)->execute();
    expect($count)->toBe(0);
});
