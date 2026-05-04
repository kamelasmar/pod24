<?php

namespace Database\Factories;

use App\Modules\Content\Models\FaqItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqItemFactory extends Factory
{
    protected $model = FaqItem::class;

    public function definition(): array
    {
        return [
            'question' => ['en' => $this->faker->sentence().' ?'],
            'answer' => ['en' => $this->faker->paragraph()],
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
