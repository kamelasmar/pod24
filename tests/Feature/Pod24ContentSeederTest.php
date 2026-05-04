<?php

use App\Modules\Content\Models\FaqItem;
use App\Modules\Content\Models\Testimonial;
use App\Modules\Content\Models\UseCase;

it('seeds 6 FAQs, 3 testimonials, and 4 use cases', function () {
    $this->seed(\Database\Seeders\Pod24ContentSeeder::class);

    expect(FaqItem::count())->toBe(6);
    expect(Testimonial::count())->toBe(3);
    expect(UseCase::count())->toBe(4);

    expect(FaqItem::where('question->en', 'Where is Pod24 located?')->exists())->toBeTrue();
    expect(UseCase::where('title->en', 'Interview podcasts')->exists())->toBeTrue();
});
