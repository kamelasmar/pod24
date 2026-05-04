<?php

use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

beforeEach(function () {
    $this->withoutVite();
});

it('shows the confirmation details for a valid ulid', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'contact_email' => 'guest@example.com',
    ]);

    $this->get('/book/confirmed?ulid=' . $booking->ulid)
        ->assertOk()
        ->assertSee('Booking confirmed')
        ->assertSee('guest@example.com')
        ->assertSee($booking->ulid);
});
