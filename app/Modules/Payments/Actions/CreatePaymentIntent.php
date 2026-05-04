<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;

class CreatePaymentIntent
{
    /** @var callable */
    private $createIntent;

    public function __construct(?callable $createIntent = null)
    {
        $this->createIntent = $createIntent ?? function (array $params) {
            \Stripe\Stripe::setApiKey(config('stripe.secret'));
            return \Stripe\PaymentIntent::create($params);
        };
    }

    public function execute(Booking $booking): array
    {
        $intent = ($this->createIntent)([
            'amount' => $booking->total_aed_cents,
            'currency' => 'aed',
            'metadata' => ['booking_ulid' => $booking->ulid],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        $booking->update([
            'stripe_payment_intent_id' => $intent->id,
            'status' => BookingStatus::PendingPayment->value,
        ]);

        return [
            'payment_intent_id' => $intent->id,
            'client_secret' => $intent->client_secret,
        ];
    }
}
