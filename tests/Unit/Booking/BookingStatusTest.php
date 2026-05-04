<?php

use App\Modules\Booking\Enums\BookingStatus;

it('exposes the 5 lifecycle states', function () {
    expect(BookingStatus::cases())->toHaveCount(5);
    expect(BookingStatus::Hold->value)->toBe('hold');
    expect(BookingStatus::PendingPayment->value)->toBe('pending_payment');
    expect(BookingStatus::Confirmed->value)->toBe('confirmed');
    expect(BookingStatus::Completed->value)->toBe('completed');
    expect(BookingStatus::Cancelled->value)->toBe('cancelled');
});

it('reports whether a status occupies a calendar slot', function () {
    expect(BookingStatus::Hold->occupiesSlot())->toBeTrue();
    expect(BookingStatus::PendingPayment->occupiesSlot())->toBeTrue();
    expect(BookingStatus::Confirmed->occupiesSlot())->toBeTrue();
    expect(BookingStatus::Completed->occupiesSlot())->toBeFalse();
    expect(BookingStatus::Cancelled->occupiesSlot())->toBeFalse();
});
