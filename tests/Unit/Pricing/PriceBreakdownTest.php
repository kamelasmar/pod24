<?php

use App\Modules\Pricing\ValueObjects\PriceBreakdown;

it('totals base + markups + addons - credits and adds VAT', function () {
    $b = new PriceBreakdown(
        base_aed_cents: 100000,
        weekend_markup_aed_cents: 5000,
        after_hours_markup_aed_cents: 3000,
        addons_aed_cents: 20000,
        hour_pack_credit_value_aed_cents: 10000,
    );

    expect($b->subtotal())->toBe(118000);   // 100k + 5k + 3k + 20k - 10k
    expect($b->vat())->toBe(5900);          // 5% of 118000
    expect($b->total())->toBe(123900);      // subtotal + vat
});

it('produces zero VAT and zero total for an empty breakdown', function () {
    $b = new PriceBreakdown();
    expect($b->subtotal())->toBe(0);
    expect($b->vat())->toBe(0);
    expect($b->total())->toBe(0);
});

it('rounds VAT half-up (banker neutral) when subtotal × 5% has half-cent', function () {
    // 199 cents × 0.05 = 9.95 cents → round to 10
    $b = new PriceBreakdown(base_aed_cents: 199);
    expect($b->vat())->toBe(10);
});
