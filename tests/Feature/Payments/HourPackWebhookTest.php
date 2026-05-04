<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Customers\Models\HourPackTransaction;

it('credits hours when checkout.session.completed fires for a pack purchase', function () {
    config(['stripe.webhook_secret' => 'whsec_test']);

    $user = User::factory()->create();
    $facility = Facility::factory()->create();
    $pack = HourPack::factory()->for($facility)->create(['hours' => 10, 'expiry_days' => 365]);

    $payload = json_encode([
        'id' => 'evt_test',
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_test_xyz',
            'metadata' => [
                'customer_id' => (string) $user->id,
                'hour_pack_id' => (string) $pack->id,
            ],
            'payment_intent' => 'pi_pack_xyz',
        ]],
    ]);

    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test');
    $header = "t={$timestamp},v1={$signature}";

    $this->postJson('/webhooks/stripe', json_decode($payload, true), [
        'Stripe-Signature' => $header,
    ])->assertOk();

    $tx = HourPackTransaction::where('customer_id', $user->id)->first();
    expect($tx)->not->toBeNull();
    expect($tx->hours)->toBe(10);
    expect($tx->type)->toBe('purchase');
    expect($tx->facility_id)->toBe($facility->id);
    expect($tx->stripe_charge_id)->toBe('pi_pack_xyz');
    expect((int) round(abs($tx->expires_at->diffInDays(now()))))->toBeBetween(364, 366);
});
