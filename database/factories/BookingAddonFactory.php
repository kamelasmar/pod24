<?php

namespace Database\Factories;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingAddon;
use App\Modules\Catalog\Models\Addon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingAddonFactory extends Factory
{
    protected $model = BookingAddon::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'addon_id' => Addon::factory(),
            'qty' => 1,
            'price_at_booking_aed_cents' => 50000,
        ];
    }
}
