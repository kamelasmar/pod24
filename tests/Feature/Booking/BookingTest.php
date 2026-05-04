<?php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('creates a booking with a ulid and integer money columns', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    $booking = Booking::factory()
        ->for($facility)
        ->for($tier, 'serviceTier')
        ->create([
            'package_type' => 'hourly',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(2),
            'total_hours' => 2,
            'subtotal_aed_cents' => 50800,
            'vat_aed_cents' => 2540,
            'total_aed_cents' => 53340,
            'status' => BookingStatus::Hold->value,
            'contact_email' => 'guest@example.com',
            'contact_name' => 'Test Guest',
            'address' => ['city' => 'Abu Dhabi', 'country' => 'AE'],
        ]);

    expect($booking->ulid)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
    expect($booking->total_aed_cents)->toBe(53340);
    expect($booking->status)->toBe(BookingStatus::Hold);
    expect($booking->address['city'])->toBe('Abu Dhabi');
});

it('casts the status column to BookingStatus enum on retrieval', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create();
    expect($booking->fresh()->status)->toBeInstanceOf(BookingStatus::class);
});
