<?php

use App\Modules\Content\Models\UseCase;

it('creates a use case with translatable title and description', function () {
    $useCase = UseCase::factory()->create([
        'title' => ['en' => 'Interview podcasts'],
        'description' => ['en' => 'Up to 4 guests, 4K multi-cam, clean audio tracks.'],
        'is_published' => true,
        'sort_order' => 1,
    ]);

    expect($useCase->fresh()->getTranslation('title', 'en'))->toBe('Interview podcasts');
    expect($useCase->fresh()->getTranslation('description', 'en'))->toBe('Up to 4 guests, 4K multi-cam, clean audio tracks.');
    expect($useCase->is_published)->toBeTrue();
    expect($useCase->sort_order)->toBe(1);
});
