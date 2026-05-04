<?php

use App\Modules\Content\Models\FaqItem;

it('creates a FAQ item with translatable question and answer', function () {
    $faq = FaqItem::factory()->create([
        'question' => ['en' => 'Where in the UAE will Pod24 travel?'],
        'answer' => ['en' => 'Standard delivery covers Abu Dhabi and Dubai.'],
        'is_published' => true,
        'sort_order' => 1,
    ]);

    expect($faq->fresh()->getTranslation('question', 'en'))->toBe('Where in the UAE will Pod24 travel?');
    expect($faq->fresh()->getTranslation('answer', 'en'))->toBe('Standard delivery covers Abu Dhabi and Dubai.');
    expect($faq->is_published)->toBeTrue();
    expect($faq->sort_order)->toBe(1);
});
