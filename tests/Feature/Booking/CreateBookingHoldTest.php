<?php

use App\Modules\Booking\Actions\CreateBookingHold;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->facility = Facility::factory()->create(['max_concurrent_per_day' => 1]);
    \App\Modules\Availability\Models\AvailabilityRule::factory()->for($this->facility)->create([
        'day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '18:00',
    ]);
    $this->tier = ServiceTier::factory()->for($this->facility)->create();
    FacilityPricing::create([
        'facility_id' => $this->facility->id,
        'service_tier_id' => $this->tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);
});

it('creates a hold with status=hold and hold_expires_at 15 min out', function () {
    $draft = new BookingDraft(
        facility_id: $this->facility->id,
        service_tier_id: $this->tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 12:00:00', 'Asia/Dubai'),
    );

    $booking = app(CreateBookingHold::class)->execute(
        draft: $draft,
        contact: ['name' => 'Guest', 'email' => 'g@example.com', 'phone' => null],
        address: ['city' => 'Abu Dhabi', 'country' => 'AE'],
    );

    expect($booking->status)->toBe(BookingStatus::Hold);
    expect($booking->hold_expires_at)->not->toBeNull();
    expect(abs($booking->hold_expires_at->diffInMinutes(now())))->toBeBetween(14, 16);
    expect($booking->total_aed_cents)->toBe(53340); // (25400 × 2) × 1.05
});

it('rejects when capacity is full', function () {
    Booking::factory()->for($this->facility)->for($this->tier, 'serviceTier')->create([
        'starts_at' => '2026-06-08 09:00:00',
        'ends_at'   => '2026-06-08 11:00:00',
        'status' => BookingStatus::Confirmed->value,
    ]);

    $draft = new BookingDraft(
        facility_id: $this->facility->id,
        service_tier_id: $this->tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 14:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 15:00:00', 'Asia/Dubai'),
    );

    expect(fn () => app(CreateBookingHold::class)->execute(
        draft: $draft,
        contact: ['name' => 'Guest', 'email' => 'g@example.com', 'phone' => null],
        address: ['city' => 'Abu Dhabi', 'country' => 'AE'],
    ))->toThrow(\App\Modules\Booking\Exceptions\SlotUnavailable::class);
});
