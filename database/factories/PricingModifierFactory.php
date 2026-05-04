<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\PricingModifier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingModifierFactory extends Factory
{
    protected $model = PricingModifier::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'type' => 'weekend',
            'percentage' => 25,
            'after_hours_start' => null,
            'after_hours_end' => null,
        ];
    }
}
