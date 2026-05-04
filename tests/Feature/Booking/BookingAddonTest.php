<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingAddon;
use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('attaches an addon to a booking with snapshot price', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create();
    $addon = Addon::factory()->for($facility)->create(['price_aed_cents' => 50000]);

    $row = BookingAddon::create([
        'booking_id' => $booking->id,
        'addon_id' => $addon->id,
        'qty' => 2,
        'price_at_booking_aed_cents' => 50000,
    ]);

    expect($row->qty)->toBe(2);
    expect($row->price_at_booking_aed_cents)->toBe(50000);
    expect($booking->fresh()->addons)->toHaveCount(1);
});
