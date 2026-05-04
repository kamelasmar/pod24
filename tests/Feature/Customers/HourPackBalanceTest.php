<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Actions\HourPackBalance;
use App\Modules\Customers\Models\HourPackTransaction;

it('sums hours for a customer-facility pair across un-expired rows', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    HourPackTransaction::factory()->create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase', 'expires_at' => now()->addMonth(),
    ]);
    HourPackTransaction::factory()->create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => -3, 'type' => 'redeem', 'expires_at' => null,
    ]);

    expect(app(HourPackBalance::class)->forCustomer($user->id, $facility->id))->toBe(7);
});

it('excludes expired rows', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    HourPackTransaction::factory()->create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase', 'expires_at' => now()->subDay(),
    ]);

    expect(app(HourPackBalance::class)->forCustomer($user->id, $facility->id))->toBe(0);
});
