<?php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('confirms a booking when payment_intent.succeeded fires', function () {
    config(['stripe.webhook_secret' => 'whsec_test']);

    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::PendingPayment->value,
        'stripe_payment_intent_id' => 'pi_abc',
    ]);

    $payload = json_encode([
        'id' => 'evt_test',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id' => 'pi_abc',
            'metadata' => ['booking_ulid' => $booking->ulid],
        ]],
    ]);

    // Build a valid Stripe signature
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_test');
    $header = "t={$timestamp},v1={$signature}";

    $this->postJson('/webhooks/stripe', json_decode($payload, true), [
        'Stripe-Signature' => $header,
    ])->assertOk();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('rejects requests without a valid signature', function () {
    config(['stripe.webhook_secret' => 'whsec_test']);

    $this->postJson('/webhooks/stripe', ['type' => 'payment_intent.succeeded'])
        ->assertStatus(400);
});
