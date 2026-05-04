<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class CancellationPolicyFactory extends Factory
{
    protected $model = CancellationPolicy::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'hours_before_min' => 168,
            'refund_percentage' => 100,
        ];
    }
}
