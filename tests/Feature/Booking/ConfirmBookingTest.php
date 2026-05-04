<?php

use App\Modules\Booking\Actions\ConfirmBooking;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Support\Facades\Event;

it('flips pending_payment to confirmed and fires the event', function () {
    Event::fake([BookingConfirmed::class]);
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::PendingPayment->value,
        'stripe_payment_intent_id' => 'pi_test_123',
    ]);

    app(ConfirmBooking::class)->execute($booking, paymentIntentId: 'pi_test_123');

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
    expect($booking->fresh()->paid_at)->not->toBeNull();
    Event::assertDispatched(BookingConfirmed::class);
});

it('is idempotent on already-confirmed bookings', function () {
    Event::fake([BookingConfirmed::class]);
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::Confirmed->value,
        'paid_at' => now()->subHour(),
    ]);
    $originalPaidAt = $booking->paid_at;

    app(ConfirmBooking::class)->execute($booking, paymentIntentId: 'pi_test_123');

    expect($booking->fresh()->paid_at->toDateTimeString())->toBe($originalPaidAt->toDateTimeString());
    Event::assertNotDispatched(BookingConfirmed::class);
});
