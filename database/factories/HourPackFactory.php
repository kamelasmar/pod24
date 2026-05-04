<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;
use Illuminate\Database\Eloquent\Factories\Factory;

class HourPackFactory extends Factory
{
    protected $model = HourPack::class;

    public function definition(): array
    {
        $hours = $this->faker->randomElement([10, 20, 40]);
        return [
            'facility_id' => Facility::factory(),
            'name' => ['en' => "{$hours}-Hour Pack"],
            'description' => ['en' => 'Pre-paid recording hours, valid for 12 months.'],
            'hours' => $hours,
            'price_aed_cents' => intdiv($hours * 25400 * 9, 10),
            'expiry_days' => 365,
            'is_active' => true,
        ];
    }
}
