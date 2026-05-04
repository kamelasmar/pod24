<?php

use App\Modules\Content\Models\FaqItem;

it('creates a FAQ item with translatable question and answer', function () {
    $faq = FaqItem::factory()->create([
        'question' => ['en' => 'Where is Pod24 located?'],
        'answer' => ['en' => 'Pod24 is at Yas Creative Hub in Abu Dhabi.'],
        'is_published' => true,
        'sort_order' => 1,
    ]);

    expect($faq->fresh()->getTranslation('question', 'en'))->toBe('Where is Pod24 located?');
    expect($faq->fresh()->getTranslation('answer', 'en'))->toBe('Pod24 is at Yas Creative Hub in Abu Dhabi.');
    expect($faq->is_published)->toBeTrue();
    expect($faq->sort_order)->toBe(1);
});
