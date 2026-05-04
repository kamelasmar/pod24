<?php

use App\Modules\Catalog\Models\Facility;

it('creates a facility with translatable name and description', function () {
    $facility = Facility::factory()->create([
        'slug' => 'pod24-portable',
        'name' => ['en' => 'Pod24 Portable Studio'],
        'description' => ['en' => 'Broadcast-grade portable podcast pod.'],
        'is_active' => true,
    ]);

    expect($facility->fresh()->getTranslation('name', 'en'))->toBe('Pod24 Portable Studio');
    expect($facility->is_active)->toBeTrue();
});

it('stores name as JSON to support multiple locales', function () {
    $facility = Facility::factory()->create([
        'name' => ['en' => 'Pod24', 'ar' => 'بود٢٤'],
    ]);

    expect($facility->fresh()->getTranslation('name', 'ar'))->toBe('بود٢٤');
});

it('defaults max_concurrent_per_day to 1', function () {
    $facility = Facility::factory()->create();

    expect($facility->fresh()->max_concurrent_per_day)->toBe(1);
});
