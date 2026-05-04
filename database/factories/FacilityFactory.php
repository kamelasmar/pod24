<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => ['en' => $this->faker->company().' Studio'],
            'description' => ['en' => $this->faker->paragraph()],
            'address' => ['city' => 'Abu Dhabi', 'country' => 'AE'],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
