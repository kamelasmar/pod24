<?php

namespace Database\Factories;

use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityRuleFactory extends Factory
{
    protected $model = AvailabilityRule::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'open_time' => '09:00',
            'close_time' => '18:00',
        ];
    }
}
