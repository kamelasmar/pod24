<?php

namespace Database\Factories;

use App\Modules\Content\Models\UseCase;
use Illuminate\Database\Eloquent\Factories\Factory;

class UseCaseFactory extends Factory
{
    protected $model = UseCase::class;

    public function definition(): array
    {
        return [
            'title' => ['en' => $this->faker->words(2, true)],
            'description' => ['en' => $this->faker->sentence()],
            'image_path' => null,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
