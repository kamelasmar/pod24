<?php

namespace App\Modules\Payments\Webhooks;

use App\Modules\Booking\Actions\ConfirmBooking;
use App\Modules\Booking\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController
{
    public function __construct(private ConfirmBooking $confirmBooking) {}

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        Log::info("Stripe webhook received: {$event->type} ({$event->id})");

        if ($event->type === 'payment_intent.succeeded') {
            $intentId = $event->data->object->id;
            $bookingUlid = $event->data->object->metadata->booking_ulid ?? null;

            $booking = $bookingUlid
                ? Booking::where('ulid', $bookingUlid)->first()
                : Booking::where('stripe_payment_intent_id', $intentId)->first();

            if ($booking) {
                $this->confirmBooking->execute($booking, $intentId);
            }
        }

        return response()->json(['received' => true]);
    }
}
