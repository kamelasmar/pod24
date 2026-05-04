<?php

use App\Livewire\BookingWizard;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $this->facility = Facility::where('slug', 'pod24-portable')->first();
});

it('mounts at step 1 (service tier) with the Pod24 facility pre-selected', function () {
    Livewire::test(BookingWizard::class)
        ->assertSet('step', 1)
        ->assertSet('facilityId', $this->facility->id);
});

it('selectTier moves to step 2 (date & time)', function () {
    $tier = ServiceTier::where('facility_id', $this->facility->id)->first();
    Livewire::test(BookingWizard::class)
        ->call('selectTier', $tier->id)
        ->assertSet('step', 2)
        ->assertSet('serviceTierId', $tier->id);
});

it('selectSlot moves to step 3 (add-ons) and stores date+time', function () {
    Livewire::test(BookingWizard::class)
        ->call('selectSlot', '2026-06-08', '10:00')
        ->assertSet('step', 3)
        ->assertSet('date', '2026-06-08')
        ->assertSet('time', '10:00');
});

it('discards a deep-linked tier_id that does not belong to Pod24', function () {
    $other = Facility::factory()->create();
    $foreignTier = ServiceTier::factory()->for($other)->create();

    Livewire::withQueryParams(['tier' => $foreignTier->id])
        ->test(BookingWizard::class)
        ->assertSet('serviceTierId', null)
        ->assertSet('step', 1);
});
