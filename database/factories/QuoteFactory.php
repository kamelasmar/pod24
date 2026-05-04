<?php

namespace Database\Factories;

use App\Modules\Quotes\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'type' => 'corporate',
            'status' => 'new',
            'event_type' => $this->faker->randomElement(['conference', 'brand_series', 'offsite', 'takeover', 'other']),
            'attendees_estimate' => '50-200',
            'days_estimate' => '1',
            'location_preference' => $this->faker->randomElement(['studio', 'on_location', 'both']),
            'service_interests' => [],
            'preferred_dates' => 'June 2026',
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_company' => $this->faker->company(),
            'message' => $this->faker->paragraph(),
        ];
    }
}
