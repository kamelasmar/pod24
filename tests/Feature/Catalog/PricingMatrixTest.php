<?php

use App\Filament\Resources\Catalog\FacilityResource\Pages\PricingMatrix;
use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('saves pricing matrix entries', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    \Livewire\Livewire::test(PricingMatrix::class, ['record' => $facility->id])
        ->fillForm([
            "tier_{$tier->id}_hourly" => 25400,
            "tier_{$tier->id}_half_day" => 90000,
        ])
        ->call('save');

    expect(FacilityPricing::count())->toBe(2);
    expect(FacilityPricing::where('package_type', 'hourly')->first()->price_aed_cents)->toBe(25400);
});
