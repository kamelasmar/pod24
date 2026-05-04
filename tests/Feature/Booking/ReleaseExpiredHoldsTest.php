<?php

use App\Modules\Booking\Actions\ReleaseExpiredHolds;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

beforeEach(function () {
    $this->facility = Facility::factory()->create();
    $this->tier = ServiceTier::factory()->for($this->facility)->create();
});

it('marks expired hold rows as cancelled', function () {
    $expired = Booking::factory()->for($this->facility)->for($this->tier, 'serviceTier')->create([
        'status' => BookingStatus::Hold->value,
        'hold_expires_at' => now()->subMinutes(1),
    ]);

    $count = app(ReleaseExpiredHolds::class)->execute();
    expect($count)->toBe(1);
    expect($expired->fresh()->status)->toBe(BookingStatus::Cancelled);
    expect($expired->fresh()->cancelled_by)->toBe('admin'); // system release
});

it('does not touch non-expired holds', function () {
    $live = Booking::factory()->for($this->facility)->for($this->tier, 'serviceTier')->create([
        'status' => BookingStatus::Hold->value,
        'hold_expires_at' => now()->addMinutes(10),
    ]);

    app(ReleaseExpiredHolds::class)->execute();
    expect($live->fresh()->status)->toBe(BookingStatus::Hold);
});

it('does not touch confirmed bookings', function () {
    $confirmed = Booking::factory()->for($this->facility)->for($this->tier, 'serviceTier')->create([
        'status' => BookingStatus::Confirmed->value,
        'hold_expires_at' => null,
    ]);

    app(ReleaseExpiredHolds::class)->execute();
    expect($confirmed->fresh()->status)->toBe(BookingStatus::Confirmed);
});
