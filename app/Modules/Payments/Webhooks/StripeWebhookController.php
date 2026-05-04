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

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $customerId = $session->metadata->customer_id ?? null;
            $packId = $session->metadata->hour_pack_id ?? null;
            $piId = $session->payment_intent ?? null;

            if ($customerId && $packId) {
                $pack = \App\Modules\Catalog\Models\HourPack::find($packId);
                if ($pack) {
                    \App\Modules\Customers\Models\HourPackTransaction::create([
                        'customer_id' => (int) $customerId,
                        'facility_id' => $pack->facility_id,
                        'hours' => $pack->hours,
                        'type' => 'purchase',
                        'stripe_charge_id' => $piId,
                        'expires_at' => now()->addDays($pack->expiry_days),
                        'notes' => "Pack purchase: {$pack->getTranslation('name', 'en')}",
                    ]);
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
