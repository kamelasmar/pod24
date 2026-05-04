<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddonFactory extends Factory
{
    protected $model = Addon::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'name' => ['en' => $this->faker->randomElement(['Episode editing', 'Social clips pack', 'Cover art', 'Distribution'])],
            'description' => ['en' => $this->faker->sentence()],
            'price_aed_cents' => $this->faker->numberBetween(10000, 200000),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
