<?php

use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Facility;

it('creates an availability rule belonging to a facility', function () {
    $facility = Facility::factory()->create();

    $rule = AvailabilityRule::create([
        'facility_id' => $facility->id,
        'day_of_week' => 1,
        'open_time' => '09:00',
        'close_time' => '18:00',
    ]);

    expect($rule->facility->is($facility))->toBeTrue();
    expect($rule->fresh()->open_time)->toBe('09:00:00');
});

it('enforces uniqueness on (facility_id, day_of_week)', function () {
    $facility = Facility::factory()->create();

    AvailabilityRule::create([
        'facility_id' => $facility->id,
        'day_of_week' => 1,
        'open_time' => '09:00',
        'close_time' => '18:00',
    ]);

    expect(fn () => AvailabilityRule::create([
        'facility_id' => $facility->id,
        'day_of_week' => 1,
        'open_time' => '10:00',
        'close_time' => '20:00',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
