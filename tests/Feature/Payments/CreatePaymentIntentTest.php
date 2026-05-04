<?php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Payments\Actions\CreatePaymentIntent;

it('creates a Stripe PaymentIntent and flips booking to pending_payment', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::Hold->value,
        'total_aed_cents' => 53340,
    ]);

    $stub = function (array $params) {
        expect($params['amount'])->toBe(53340);
        expect($params['currency'])->toBe('aed');
        return (object) [
            'id' => 'pi_test_xyz',
            'client_secret' => 'pi_test_xyz_secret_abc',
        ];
    };

    $action = new CreatePaymentIntent($stub);
    $result = $action->execute($booking);

    expect($result['client_secret'])->toBe('pi_test_xyz_secret_abc');
    expect($booking->fresh()->stripe_payment_intent_id)->toBe('pi_test_xyz');
    expect($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});
