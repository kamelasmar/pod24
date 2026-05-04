<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacilityPricingFactory extends Factory
{
    protected $model = FacilityPricing::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'service_tier_id' => ServiceTier::factory(),
            'package_type' => 'hourly',
            'hours' => 1,
            'price_aed_cents' => 25400,
        ];
    }
}
