<?php

namespace Database\Factories;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 week', '+3 weeks');
        $end = (clone $start)->modify('+2 hours');

        return [
            'facility_id' => Facility::factory(),
            'service_tier_id' => ServiceTier::factory(),
            'package_type' => 'hourly',
            'starts_at' => $start,
            'ends_at' => $end,
            'total_hours' => 2,
            'status' => BookingStatus::Hold->value,
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'address' => ['city' => 'Abu Dhabi', 'country' => 'AE'],
            'subtotal_aed_cents' => 50800,
            'vat_aed_cents' => 2540,
            'total_aed_cents' => 53340,
        ];
    }
}
