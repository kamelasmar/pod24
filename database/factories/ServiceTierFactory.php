<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceTierFactory extends Factory
{
    protected $model = ServiceTier::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'name' => $this->faker->randomElement(['Recording Only', 'Live Mix', 'Live Mix + Edit']),
            'description' => ['en' => $this->faker->sentence()],
            'base_hourly_rate_aed_cents' => $this->faker->numberBetween(10000, 100000),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
