<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Models\HourPackTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class HourPackTransactionFactory extends Factory
{
    protected $model = HourPackTransaction::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'facility_id' => Facility::factory(),
            'hours' => 10,
            'type' => 'purchase',
            'expires_at' => now()->addYear(),
        ];
    }
}
