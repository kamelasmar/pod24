<?php

use App\Livewire\BookingWizard;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $this->facility = Facility::where('slug', 'pod24-portable')->first();
});

it('mounts at step 1 with the Pod24 facility pre-selected', function () {
    Livewire::test(BookingWizard::class)
        ->assertSet('step', 1)
        ->assertSet('facilityId', $this->facility->id);
});

it('selectTier moves to step 3 and stores the tier id', function () {
    $tier = ServiceTier::where('facility_id', $this->facility->id)->first();
    Livewire::test(BookingWizard::class)
        ->call('selectTier', $tier->id)
        ->assertSet('step', 3)
        ->assertSet('serviceTierId', $tier->id);
});

it('redirects out-of-AD addresses to quote/offsite', function () {
    Livewire::test(BookingWizard::class)
        ->set('step', 4)
        ->set('address.city', 'Dubai')
        ->call('submitAddress')
        ->assertRedirect();
});

it('proceeds to step 5 when the address is in Abu Dhabi', function () {
    Livewire::test(BookingWizard::class)
        ->set('step', 4)
        ->set('address.city', 'Abu Dhabi')
        ->call('submitAddress')
        ->assertSet('step', 5);
});
