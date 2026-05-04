<?php

use App\Modules\Availability\Actions\FindAvailableSlots;
use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->facility = Facility::factory()->create(['max_concurrent_per_day' => 2]);
    foreach ([1, 2, 3, 4, 5, 6] as $dow) {
        AvailabilityRule::factory()->for($this->facility)->create([
            'day_of_week' => $dow,
            'open_time' => '09:00',
            'close_time' => '18:00',
        ]);
    }
});

it('returns hourly slots for a Monday inside business hours', function () {
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');  // Monday
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $monday, 1,
    );

    expect($slots)->toHaveCount(9);  // 09:00, 10:00, ..., 17:00 starts (9 1-hour slots)
    expect($slots[0]->starts_at->format('H:i'))->toBe('09:00');
    expect($slots[0]->ends_at->format('H:i'))->toBe('10:00');
});

it('returns no slots on a closed day', function () {
    $sunday = CarbonImmutable::parse('2026-06-07', 'Asia/Dubai');  // Sunday — closed
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $sunday, 1,
    );
    expect($slots)->toHaveCount(0);
});

it('excludes slots that overlap a blackout', function () {
    AvailabilityBlackout::create([
        'facility_id' => $this->facility->id,
        'starts_at' => '2026-06-08 11:00:00',
        'ends_at'   => '2026-06-08 14:00:00',
        'reason' => 'Maintenance',
    ]);

    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $monday, 1,
    );

    // Original 9 slots minus 11:00, 12:00, 13:00 = 6
    expect($slots)->toHaveCount(6);
    foreach ($slots as $slot) {
        expect($slot->starts_at->format('H:i'))->not->toBeIn(['11:00', '12:00', '13:00']);
    }
});

it('returns 4-hour slots when duration=4', function () {
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $monday, 4,
    );

    // 09:00 (4h ends 13:00), 10:00 (10:00->14:00), ..., 14:00 (14:00->18:00) = 6 starts
    expect($slots)->toHaveCount(6);
    expect($slots[0]->ends_at->format('H:i'))->toBe('13:00');
});

it('returns 8-hour slots when duration=8', function () {
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $monday, 8,
    );

    // Open 09-18 = 9 hours; 09:00->17:00 (8h) and 10:00->18:00 (8h) = 2 slots.
    expect($slots)->toHaveCount(2);
});

it('excludes the day when concurrent occupying bookings >= capacity', function () {
    $tier = ServiceTier::factory()->for($this->facility)->create();
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');

    // capacity is 2 (set in beforeEach); fill it with 2 confirmed bookings
    Booking::factory()->count(2)->for($this->facility)->for($tier, 'serviceTier')->create([
        'starts_at' => $monday->setTime(10, 0),
        'ends_at' => $monday->setTime(12, 0),
        'status' => BookingStatus::Confirmed->value,
    ]);

    $slots = app(FindAvailableSlots::class)->execute($this->facility->id, $monday, 1);
    expect($slots)->toHaveCount(0);
});

it('counts holds and pending_payment bookings against capacity', function () {
    $tier = ServiceTier::factory()->for($this->facility)->create();
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');

    Booking::factory()->for($this->facility)->for($tier, 'serviceTier')->create([
        'starts_at' => $monday->setTime(9, 0),
        'ends_at' => $monday->setTime(10, 0),
        'status' => BookingStatus::Hold->value,
        'hold_expires_at' => now()->addMinutes(10),
    ]);
    Booking::factory()->for($this->facility)->for($tier, 'serviceTier')->create([
        'starts_at' => $monday->setTime(11, 0),
        'ends_at' => $monday->setTime(12, 0),
        'status' => BookingStatus::PendingPayment->value,
    ]);

    $slots = app(FindAvailableSlots::class)->execute($this->facility->id, $monday, 1);
    expect($slots)->toHaveCount(0); // capacity 2 already filled by hold + pending
});

it('does not count cancelled or completed bookings against capacity', function () {
    $tier = ServiceTier::factory()->for($this->facility)->create();
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');

    Booking::factory()->count(2)->for($this->facility)->for($tier, 'serviceTier')->create([
        'starts_at' => $monday->setTime(10, 0),
        'ends_at' => $monday->setTime(12, 0),
        'status' => BookingStatus::Cancelled->value,
    ]);

    $slots = app(FindAvailableSlots::class)->execute($this->facility->id, $monday, 1);
    expect(count($slots))->toBeGreaterThan(0);
});
