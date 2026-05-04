<?php

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Pricing\Actions\LookupRefundPercentage;

beforeEach(function () {
    $this->facility = Facility::factory()->create();
    CancellationPolicy::factory()->for($this->facility)->create(['hours_before_min' => 168, 'refund_percentage' => 100]);
    CancellationPolicy::factory()->for($this->facility)->create(['hours_before_min' => 72,  'refund_percentage' => 50]);
    CancellationPolicy::factory()->for($this->facility)->create(['hours_before_min' => 0,   'refund_percentage' => 0]);
});

it('returns 100 when cancelling more than 7 days out', function () {
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 200))->toBe(100);
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 168))->toBe(100);
});

it('returns 50 when 3-7 days out', function () {
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 167))->toBe(50);
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 72))->toBe(50);
});

it('returns 0 when less than 3 days out', function () {
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 71))->toBe(0);
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 0))->toBe(0);
});

it('throws if no policy is configured for the facility', function () {
    $other = Facility::factory()->create();
    expect(fn () => app(LookupRefundPercentage::class)->execute($other->id, 100))
        ->toThrow(\App\Modules\Pricing\Exceptions\CancellationPolicyMissing::class);
});
