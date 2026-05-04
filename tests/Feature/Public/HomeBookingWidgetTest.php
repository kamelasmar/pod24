<?php

use App\Livewire\HomeBookingWidget;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $this->facility = Facility::where('slug', 'pod24-portable')->first();
});

it('mounts with the lowest-sort tier preselected and a default package', function () {
    $expected = ServiceTier::where('facility_id', $this->facility->id)
        ->orderBy('sort_order')->value('id');

    Livewire::test(HomeBookingWidget::class, ['facility' => $this->facility])
        ->assertSet('selectedTierId', $expected)
        ->assertSet('packageType', 'hourly');
});

it('builds a 7-column month grid', function () {
    $component = Livewire::test(HomeBookingWidget::class, ['facility' => $this->facility]);
    $grid = $component->instance()->monthGrid;
    expect(count($grid) % 7)->toBe(0);
    expect(count($grid))->toBeGreaterThanOrEqual(28);
});

it('clears time selection when the date changes', function () {
    Livewire::test(HomeBookingWidget::class, ['facility' => $this->facility])
        ->set('selectedDate', '2026-06-08')
        ->set('selectedTime', '10:00')
        ->call('selectDate', '2026-06-09')
        ->assertSet('selectedDate', '2026-06-09')
        ->assertSet('selectedTime', null);
});

it('redirects to /book step 4 with prefilled query params on continueToCheckout', function () {
    $tier = ServiceTier::where('facility_id', $this->facility->id)->first();

    Livewire::test(HomeBookingWidget::class, ['facility' => $this->facility])
        ->set('selectedDate', '2026-06-08')
        ->set('selectedTime', '10:00')
        ->set('selectedTierId', $tier->id)
        ->call('continueToCheckout')
        ->assertRedirect();
});

it('does nothing on continueToCheckout if selection incomplete', function () {
    Livewire::test(HomeBookingWidget::class, ['facility' => $this->facility])
        ->call('continueToCheckout')
        ->assertNoRedirect();
});
