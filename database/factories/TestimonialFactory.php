<?php

namespace Database\Factories;

use App\Modules\Content\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'quote' => ['en' => $this->faker->paragraph()],
            'name' => $this->faker->name(),
            'role' => $this->faker->jobTitle().' · '.$this->faker->company(),
            'avatar_path' => null,
            'is_published' => true,
        ];
    }
}
