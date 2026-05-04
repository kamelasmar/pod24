<?php

namespace Database\Factories;

use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityBlackoutFactory extends Factory
{
    protected $model = AvailabilityBlackout::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 week', '+2 weeks');
        $end = (clone $start)->modify('+8 hours');

        return [
            'facility_id' => Facility::factory(),
            'starts_at' => $start,
            'ends_at' => $end,
            'reason' => $this->faker->sentence(),
        ];
    }
}
