<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Customers\Actions\PurchaseHourPack;

it('creates a Stripe Checkout session for an hour pack purchase', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();
    $pack = HourPack::factory()->for($facility)->create([
        'hours' => 10,
        'price_aed_cents' => 228600,
    ]);

    $stub = function (array $params) use ($pack) {
        expect($params['line_items'][0]['price_data']['unit_amount'])->toBe($pack->price_aed_cents);
        expect($params['metadata']['hour_pack_id'])->toBe((string) $pack->id);
        expect($params['mode'])->toBe('payment');
        expect($params['line_items'][0]['price_data']['currency'])->toBe('aed');
        return (object) ['id' => 'cs_test_xyz', 'url' => 'https://checkout.stripe.com/cs_test_xyz'];
    };

    $action = new PurchaseHourPack($stub);
    $session = $action->execute($user, $pack);

    expect($session['url'])->toBe('https://checkout.stripe.com/cs_test_xyz');
    expect($session['session_id'])->toBe('cs_test_xyz');
});
