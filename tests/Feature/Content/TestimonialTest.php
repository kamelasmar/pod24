<?php

use App\Modules\Content\Models\Testimonial;

it('creates a testimonial with translatable quote', function () {
    $testimonial = Testimonial::factory()->create([
        'quote' => ['en' => 'Pod24 was a fantastic experience.'],
        'name' => 'Jane Doe',
        'role' => 'Host · Placeholder Podcast',
        'is_published' => true,
    ]);

    expect($testimonial->fresh()->getTranslation('quote', 'en'))->toBe('Pod24 was a fantastic experience.');
    expect($testimonial->name)->toBe('Jane Doe');
    expect($testimonial->role)->toBe('Host · Placeholder Podcast');
    expect($testimonial->is_published)->toBeTrue();
});
